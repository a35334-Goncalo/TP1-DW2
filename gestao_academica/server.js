require('dotenv').config();
const dns = require('dns');
dns.setServers(['8.8.8.8', '8.8.4.4']);
const express = require('express');
const mongoose = require('mongoose');
const session = require('express-session');
const path = require('path');
const methodOverride = require('method-override');

const app = express();
const PORT = process.env.PORT || 3000;

// Connect to MongoDB
mongoose.connect(process.env.MONGODB_URI, { family: 4 })
    .then(() => console.log('Conectado à MongoDB'))
    .catch(err => console.error('Erro ao conectar à MongoDB:', err));

// Middleware
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(methodOverride('_method'));
app.use(express.static(path.join(__dirname, 'public')));
app.use('/uploads', express.static(path.join(__dirname, 'uploads')));

app.use(session({
    secret: process.env.SESSION_SECRET || 'secret',
    resave: false,
    saveUninitialized: true,
    cookie: { secure: false } // Set to true if using HTTPS
}));

// Set EJS as view engine
app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

// Global middleware for session variables
app.use((req, res, next) => {
    res.locals.user = req.session.user || null;
    res.locals.role = req.session.role || null;
    next();
});

// Routes
app.use('/', require('./routes/auth'));
app.use('/aluno', require('./routes/aluno'));
app.use('/funcionario', require('./routes/funcionario'));
app.use('/gestor', require('./routes/gestor'));

app.get('/', (req, res) => {
    if (req.session.user) {
        if (req.session.role === 'gestor') return res.redirect('/gestor');
        if (req.session.role === 'aluno') return res.redirect('/aluno');
        if (req.session.role === 'funcionario') return res.redirect('/funcionario');
    }
    res.redirect('/login');
});

app.listen(PORT, () => {
    console.log(`Servidor a correr em http://localhost:${PORT}`);
});
