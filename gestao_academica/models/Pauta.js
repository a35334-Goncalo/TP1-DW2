const mongoose = require('mongoose');

const pautaSchema = new mongoose.Schema({
    uc_id: { type: mongoose.Schema.Types.ObjectId, ref: 'UC', required: true },
    ano_letivo: { type: String, required: true },
    epoca: { type: String, required: true },
    criado_por: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
    data_criacao: { type: Date, default: Date.now }
});

module.exports = mongoose.model('Pauta', pautaSchema);
