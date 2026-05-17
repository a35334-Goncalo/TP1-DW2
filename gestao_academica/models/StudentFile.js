const mongoose = require('mongoose');

const studentFileSchema = new mongoose.Schema({
    user_id: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
    dados: { type: String },
    foto: { type: String },
    estado: { 
        type: String, 
        enum: ['rascunho', 'submetida', 'Aprovada', 'Rejeitada'], 
        default: 'rascunho' 
    },
    observacoes: { type: String },
    validado_por: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
    data_validacao: { type: Date }
});

module.exports = mongoose.model('StudentFile', studentFileSchema);
