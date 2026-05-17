const mongoose = require('mongoose');

const ucSchema = new mongoose.Schema({
    nome: { type: String, required: true },
    ativo: { type: Boolean, default: true }
});

module.exports = mongoose.model('UC', ucSchema);
