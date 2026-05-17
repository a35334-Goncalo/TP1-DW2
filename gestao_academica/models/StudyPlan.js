const mongoose = require('mongoose');

const studyPlanSchema = new mongoose.Schema({
    curso_id: { type: mongoose.Schema.Types.ObjectId, ref: 'Course', required: true },
    uc_id: { type: mongoose.Schema.Types.ObjectId, ref: 'UC', required: true },
    ano: { type: Number, required: true },
    semestre: { type: Number, required: true }
});

module.exports = mongoose.model('StudyPlan', studyPlanSchema);
