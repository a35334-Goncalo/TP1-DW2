const dns = require('dns');
dns.setServers(['8.8.8.8']);
require('dotenv').config();
const mongoose = require('mongoose');
const User = require('./models/User');

async function runSeed() {
    console.log('Tentando conectar ao MongoDB...');
    try {
        await mongoose.connect(process.env.MONGODB_URI);
        console.log('Conectado à MongoDB');
        
        await User.deleteMany({});
        console.log('Base de dados limpa.');
        
        const users = [
            { nome: 'Gestor Admin', email: 'gestor@escola.pt', password: '123456', perfil_id: 1 },
            { nome: 'Aluno Teste', email: 'aluno@escola.pt', password: '123456', perfil_id: 2 },
            { nome: 'Funcionario Sec', email: 'funcionario@escola.pt', password: '123456', perfil_id: 3 },
            { nome: 'Professor Teste', email: 'professor@escola.pt', password: '123456', perfil_id: 3 }
        ];
        
        for (let u of users) {
            const newUser = new User(u);
            await newUser.save();
            console.log(`Utilizador criado: ${u.email}`);
        }
        
        console.log('SEED CONCLUÍDO COM SUCESSO!');
        process.exit(0);
    } catch (err) {
        console.error('ERRO NO SEED:', err);
        process.exit(1);
    }
}

runSeed();
