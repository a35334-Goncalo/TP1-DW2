const express = require('express');
const router = express.Router();
const User = require('../models/User');

// GET Login Page
router.get('/login', (req, res) => {
    if (req.session.user) return res.redirect('/');
    res.render('login', { erro: null });
});

// POST Login
router.post('/login', async (req, res) => {
    const { email, password } = req.body;
    try {
        const user = await User.findOne({ email });
        if (user && await user.comparePassword(password)) {
            req.session.user = {
                id: user._id,
                nome: user.nome,
                email: user.email,
                perfil_id: user.perfil_id
            };

            if (user.perfil_id === 1) req.session.role = 'gestor';
            else if (user.perfil_id === 2) req.session.role = 'aluno';
            else if (user.perfil_id === 3) req.session.role = 'funcionario';

            return res.redirect('/');
        } else {
            res.render('login', { erro: "Login inválido" });
        }
    } catch (err) {
        console.error(err);
        res.render('login', { erro: "Erro no servidor" });
    }
});

// GET Logout
router.get('/logout', (req, res) => {
    req.session.destroy();
    res.redirect('/login');
});

// GET Seed Route (To create initial users)
router.get('/seed', async (req, res) => {
    try {
        await User.deleteMany({});
        const users = [
            { nome: 'Gestor Admin', email: 'gestor@escola.pt', password: '123456', perfil_id: 1 },
            { nome: 'Aluno Teste', email: 'aluno@escola.pt', password: '123456', perfil_id: 2 },
            { nome: 'Funcionario Sec', email: 'funcionario@escola.pt', password: '123456', perfil_id: 3 }
        ];
        for (let u of users) {
            const newUser = new User(u);
            await newUser.save();
        }
        res.send("Utilizadores de teste criados! (gestor@escola.pt, aluno@escola.pt, funcionario@escola.pt / 123456)");
    } catch (err) {
        console.error("Erro no seed:", err);
        res.status(500).send("Erro ao semear dados: " + err.message);
    }
});

module.exports = router;
