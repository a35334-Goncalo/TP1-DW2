const mongoose = require('mongoose');

const enrollmentSchema = new mongoose.Schema({
    user_id: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
    curso_id: { type: mongoose.Schema.Types.ObjectId, ref: 'Course', required: true },
    estado: { type: String, default: 'Pendente' },
    observacoes: { type: String },
    validado_por: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
    data_pedido: { type: Date, default: Date.now },
    data_validacao: { type: Date }
});

module.exports = mongoose.model('Enrollment', enrollmentSchema);
