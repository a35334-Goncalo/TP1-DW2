const express = require('express');
const router = express.Router();
const multer = require('multer');
const path = require('path');
const StudentFile = require('../models/StudentFile');
const Enrollment = require('../models/Enrollment');
const Course = require('../models/Course');

// Multer configuration
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        cb(null, 'uploads/');
    },
    filename: (req, file, cb) => {
        cb(null, 'foto_' + req.session.user.id + '_' + Date.now() + path.extname(file.originalname));
    }
});

const upload = multer({ 
    storage: storage,
    limits: { fileSize: 5 * 1024 * 1024 }, // 5MB
    fileFilter: (req, file, cb) => {
        const filetypes = /jpeg|jpg|png|gif/;
        const mimetype = filetypes.test(file.mimetype);
        const extname = filetypes.test(path.extname(file.originalname).toLowerCase());
        if (mimetype && extname) return cb(null, true);
        cb(new Error("Apenas imagens são permitidas!"));
    }
});

// Middleware to check if user is student
const isAluno = (req, res, next) => {
    if (req.session.user && req.session.role === 'aluno') return next();
    res.redirect('/login');
};

// GET Dashboard
router.get('/', isAluno, async (req, res) => {
    try {
        const ficha = await StudentFile.findOne({ user_id: req.session.user.id });
        const cursos = await Course.find({ ativo: true }).sort({ nome: 1 });
        const pedidos = await Enrollment.find({ user_id: req.session.user.id }).populate('curso_id').sort({ data_pedido: -1 });
        
        res.render('aluno', { 
            ficha: ficha || {}, 
            cursos, 
            pedidos, 
            erro: req.query.erro || null, 
            sucesso: req.query.sucesso || null 
        });
    } catch (err) {
        console.error(err);
        res.redirect('/aluno?erro=Erro ao carregar dados');
    }
});

// POST Update/Submit Ficha
router.post('/ficha', isAluno, (req, res) => {
    upload.single('foto')(req, res, async (err) => {
        if (err instanceof multer.MulterError) {
            if (err.code === 'LIMIT_FILE_SIZE') {
                return res.redirect('/aluno?erro=A imagem é muito grande (máximo 5MB).');
            }
            return res.redirect('/aluno?erro=Erro no upload da imagem.');
        } else if (err) {
            return res.redirect('/aluno?erro=' + encodeURIComponent(err.message));
        }

        const { nome, idade, contacto, curso_id, acao_ficha } = req.body;
        const estado = acao_ficha === 'submeter' ? 'submetida' : 'rascunho';
        
        try {
            let ficha = await StudentFile.findOne({ user_id: req.session.user.id });
            const updateData = {
                dados: `Nome: ${nome}, Idade: ${idade}, Contacto: ${contacto || ''}, Curso ID: ${curso_id}`,
                estado: estado
            };
            if (req.file) updateData.foto = req.file.filename;

            if (ficha) {
                await StudentFile.updateOne({ user_id: req.session.user.id }, updateData);
            } else {
                updateData.user_id = req.session.user.id;
                await new StudentFile(updateData).save();
            }

            const msg = estado === 'submetida' ? "Ficha submetida!" : "Ficha salva!";
            res.redirect(`/aluno?sucesso=${encodeURIComponent(msg)}`);
        } catch (err) {
            console.error(err);
            res.redirect('/aluno?erro=Erro ao processar ficha');
        }
    });
});

// POST Create Enrollment Request
router.post('/matricula', isAluno, async (req, res) => {
    const { curso_id } = req.body;
    try {
        const existe = await Enrollment.findOne({ user_id: req.session.user.id, curso_id: curso_id, estado: { $in: ['Pendente', 'Aprovado'] } });
        if (existe) {
            return res.redirect('/aluno?erro=Já existe um pedido pendente ou aprovado para este curso.');
        }

        await new Enrollment({
            user_id: req.session.user.id,
            curso_id: curso_id
        }).save();
        res.redirect('/aluno?sucesso=Pedido de matrícula criado!');
    } catch (err) {
        console.error(err);
        res.redirect('/aluno?erro=Erro ao criar pedido');
    }
});

module.exports = router;
