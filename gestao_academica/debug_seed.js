require('dotenv').config();
const dns = require('dns');
dns.setServers(['8.8.8.8']);
const mongoose = require('mongoose');
const User = require('./models/User');

mongoose.connect(process.env.MONGODB_URI)
    .then(async () => {
        console.log('Conectado à MongoDB');
        try {
            console.log("Iniciando deleteMany...");
            await User.deleteMany({});
            console.log("deleteMany concluído.");
            const users = [
                { nome: 'Gestor Admin', email: 'gestor@escola.pt', password: '123456', perfil_id: 1 }
            ];
            for (let u of users) {
                console.log(`Criando utilizador: ${u.email}`);
                const newUser = new User(u);
                console.log(`Salvando utilizador: ${u.email}`);
                await newUser.save();
                console.log(`Utilizador salvo: ${u.email}`);
            }
            console.log("Seed concluído com sucesso!");
        } catch (err) {
            console.error("ERRO NO SEED:", err);
        } finally {
            mongoose.connection.close();
        }
    })
    .catch(err => console.error('Erro ao conectar:', err));
