const mongoose = require('mongoose');

const courseSchema = new mongoose.Schema({
    nome: { type: String, required: true },
    ativo: { type: Boolean, default: true }
});

module.exports = mongoose.model('Course', courseSchema);
