const express = require('express');
const router = express.Router();
const StudentFile = require('../models/StudentFile');
const Course = require('../models/Course');
const UC = require('../models/UC');
const StudyPlan = require('../models/StudyPlan');
const User = require('../models/User');

// Middleware
const isGestor = (req, res, next) => {
    if (req.session.user && req.session.role === 'gestor') return next();
    res.redirect('/login');
};

// GET Dashboard
router.get('/', isGestor, async (req, res) => {
    try {
        const fichas = await StudentFile.find().populate('user_id').populate('validado_por').sort({ _id: -1 });
        const cursos = await Course.find().sort({ nome: 1 });
        const ucs = await UC.find().sort({ nome: 1 });
        const plano = await StudyPlan.find().populate('curso_id').populate('uc_id').sort({ 'curso_id.nome': 1, ano: 1, semestre: 1 });

        res.render('gestor', { 
            fichas, 
            cursos, 
            ucs, 
            plano,
            curso_edicao: null,
            uc_edicao: null,
            erro: req.query.erro || null,
            sucesso: req.query.sucesso || null
        });
    } catch (err) {
        console.error(err);
        res.redirect('/gestor?erro=Erro ao carregar dados');
    }
});

// POST Update Ficha
router.post('/ficha/:id', isGestor, async (req, res) => {
    const { estado, observacoes } = req.body;
    try {
        await StudentFile.findByIdAndUpdate(req.params.id, {
            estado,
            observacoes,
            validado_por: req.session.user.id,
            data_validacao: new Date()
        });
        res.redirect('/gestor?sucesso=Ficha atualizada!');
    } catch (err) {
        res.redirect('/gestor?erro=Erro ao atualizar ficha');
    }
});

// COURSE CRUD
router.post('/curso', isGestor, async (req, res) => {
    const { id, nome, desativar } = req.body;
    const ativo = desativar ? false : true;
    try {
        if (id) {
            await Course.findByIdAndUpdate(id, { nome, ativo });
            res.redirect('/gestor?sucesso=Curso atualizado!');
        } else {
            await new Course({ nome, ativo }).save();
            res.redirect('/gestor?sucesso=Curso criado!');
        }
    } catch (err) {
        res.redirect('/gestor?erro=Erro ao processar curso');
    }
});

router.post('/curso/delete', isGestor, async (req, res) => {
    try {
        await Course.findByIdAndDelete(req.body.id);
        res.redirect('/gestor?sucesso=Curso eliminado!');
    } catch (err) {
        res.redirect('/gestor?erro=Erro ao eliminar curso');
    }
});

// UC CRUD
router.post('/uc', isGestor, async (req, res) => {
    const { id, nome, desativar } = req.body;
    const ativo = desativar ? false : true;
    try {
        if (id) {
            await UC.findByIdAndUpdate(id, { nome, ativo });
            res.redirect('/gestor?sucesso=UC atualizada!');
        } else {
            await new UC({ nome, ativo }).save();
            res.redirect('/gestor?sucesso=UC criada!');
        }
    } catch (err) {
        res.redirect('/gestor?erro=Erro ao processar UC');
    }
});

router.post('/uc/delete', isGestor, async (req, res) => {
    try {
        await UC.findByIdAndDelete(req.body.id);
        res.redirect('/gestor?sucesso=UC eliminada!');
    } catch (err) {
        res.redirect('/gestor?erro=Erro ao eliminar UC');
    }
});

// Study Plan association
router.post('/plano', isGestor, async (req, res) => {
    const { curso_id, uc_id, ano, semestre } = req.body;
    try {
        const existe = await StudyPlan.findOne({ curso_id, uc_id, ano, semestre });
        if (existe) return res.redirect('/gestor?erro=Essa UC já está associada a este curso/ano/semestre');
        
        await new StudyPlan({ curso_id, uc_id, ano, semestre }).save();
        res.redirect('/gestor?sucesso=UC associada ao plano!');
    } catch (err) {
        res.redirect('/gestor?erro=Erro ao associar UC');
    }
});

module.exports = router;
