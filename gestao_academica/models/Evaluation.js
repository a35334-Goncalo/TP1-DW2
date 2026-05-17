const mongoose = require('mongoose');

const evaluationSchema = new mongoose.Schema({
    pauta_id: { type: mongoose.Schema.Types.ObjectId, ref: 'Pauta', required: true },
    aluno_id: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
    nota: { type: String }
});

module.exports = mongoose.model('Evaluation', evaluationSchema);
