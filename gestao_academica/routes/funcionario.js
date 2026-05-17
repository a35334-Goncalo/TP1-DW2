const express = require('express');
const router = express.Router();
const Enrollment = require('../models/Enrollment');
const UC = require('../models/UC');
const Pauta = require('../models/Pauta');
const Evaluation = require('../models/Evaluation');
const StudyPlan = require('../models/StudyPlan');
const User = require('../models/User');

// Middleware
const isFuncionario = (req, res, next) => {
    if (req.session.user && req.session.role === 'funcionario') return next();
    res.redirect('/login');
};

// GET Dashboard
router.get('/', isFuncionario, async (req, res) => {
    try {
        const pedidos = await Enrollment.find()
            .populate('user_id')
            .populate('curso_id')
            .populate('validado_por')
            .sort({ data_pedido: -1 });

        const ucs = await UC.find({ ativo: true }).sort({ nome: 1 });
        
        const pautas = await Pauta.find()
            .populate('uc_id')
            .populate('criado_por')
            .sort({ data_criacao: -1 });

        // Get evaluations for each pauta
        const pautasComAvaliacoes = await Promise.all(pautas.map(async (p) => {
            const avaliacoes = await Evaluation.find({ pauta_id: p._id }).populate('aluno_id');
            return { ...p.toObject(), avaliacoes };
        }));

        res.render('funcionario', { 
            pedidos, 
            ucs, 
            pautas: pautasComAvaliacoes,
            erro: req.query.erro || null,
            sucesso: req.query.sucesso || null
        });
    } catch (err) {
        console.error(err);
        res.redirect('/funcionario?erro=Erro ao carregar dados');
    }
});

// POST Update Enrollment
router.post('/pedido/:id', isFuncionario, async (req, res) => {
    const { estado, observacoes } = req.body;
    try {
        await Enrollment.findByIdAndUpdate(req.params.id, {
            estado,
            observacoes,
            validado_por: req.session.user.id,
            data_validacao: new Date()
        });
        res.redirect('/funcionario?sucesso=Pedido atualizado!');
    } catch (err) {
        res.redirect('/funcionario?erro=Erro ao atualizar pedido');
    }
});

// POST Create Pauta
router.post('/pauta', isFuncionario, async (req, res) => {
    const { uc_id, ano_letivo, epoca } = req.body;
    try {
        const pauta = new Pauta({
            uc_id,
            ano_letivo,
            epoca,
            criado_por: req.session.user.id
        });
        await pauta.save();

        // Get eligible students (Approved enrollment in courses that have this UC)
        const plans = await StudyPlan.find({ uc_id });
        const courseIds = plans.map(p => p.curso_id);

        const enrollments = await Enrollment.find({
            curso_id: { $in: courseIds },
            estado: 'Aprovado'
        });

        const studentIds = [...new Set(enrollments.map(e => e.user_id.toString()))];

        for (let sId of studentIds) {
            await new Evaluation({
                pauta_id: pauta._id,
                aluno_id: sId,
                nota: ''
            }).save();
        }

        res.redirect(`/funcionario?sucesso=Pauta criada com ${studentIds.length} alunos!`);
    } catch (err) {
        console.error(err);
        res.redirect('/funcionario?erro=Erro ao criar pauta');
    }
});

// POST Update Grades
router.post('/notas', isFuncionario, async (req, res) => {
    const { notas } = req.body; // { 'evalId': 'nota', ... }
    try {
        if (notas) {
            for (let [id, nota] of Object.entries(notas)) {
                await Evaluation.findByIdAndUpdate(id, { nota: nota.trim() });
            }
        }
        res.redirect('/funcionario?sucesso=Notas atualizadas!');
    } catch (err) {
        res.redirect('/funcionario?erro=Erro ao atualizar notas');
    }
});

module.exports = router;
