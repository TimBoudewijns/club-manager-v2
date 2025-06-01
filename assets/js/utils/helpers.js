// Helper functions and constants

export const evaluationCategories = [
    {
        name: 'Ball Control',
        key: 'ball_control',
        subcategories: [
            { key: 'first_touch', name: 'First Touch', description: 'Clean reception' },
            { key: 'ball_carry', name: 'Ball Carry', description: 'Control under pressure' }
        ]
    },
    {
        name: 'Passing & Receiving',
        key: 'passing_receiving',
        subcategories: [
            { key: 'push_slap_hit', name: 'Push, Slap, Hit', description: 'Accuracy & power' },
            { key: 'timing_communication', name: 'Timing & Communication', description: 'Timing and communication' }
        ]
    },
    {
        name: 'Dribbling Skills',
        key: 'dribbling_skills',
        subcategories: [
            { key: '1v1_situations', name: '1v1 Situations', description: '1v1 situations' },
            { key: 'lr_control', name: 'L/R Control', description: 'Left/right control at speed' }
        ]
    },
    {
        name: 'Defensive Skills',
        key: 'defensive_skills',
        subcategories: [
            { key: 'jab_block', name: 'Jab & Block', description: 'Jab & block tackle' },
            { key: 'marking_positioning', name: 'Marking & Positioning', description: 'Marking & positioning' }
        ]
    },
    {
        name: 'Finishing & Scoring',
        key: 'finishing_scoring',
        subcategories: [
            { key: 'shot_variety', name: 'Shot Variety', description: 'Hit, deflection, rebound' },
            { key: 'scoring_instinct', name: 'Scoring Instinct', description: 'Scoring instinct' }
        ]
    },
    {
        name: 'Tactical Understanding',
        key: 'tactical_understanding',
        subcategories: [
            { key: 'spatial_awareness', name: 'Spatial Awareness', description: 'Spatial awareness' },
            { key: 'game_intelligence', name: 'Game Intelligence', description: 'Making the right choices' }
        ]
    },
    {
        name: 'Physical Fitness',
        key: 'physical_fitness',
        subcategories: [
            { key: 'speed_endurance', name: 'Speed & Endurance', description: 'Speed & endurance' },
            { key: 'strength_agility', name: 'Strength & Agility', description: 'Strength, agility, balance' }
        ]
    },
    {
        name: 'Mental Toughness',
        key: 'mental_toughness',
        subcategories: [
            { key: 'focus_resilience', name: 'Focus & Resilience', description: 'Focus and resilience' },
            { key: 'confidence_pressure', name: 'Confidence Under Pressure', description: 'Performance under pressure' }
        ]
    },
    {
        name: 'Team Play & Communication',
        key: 'team_play',
        subcategories: [
            { key: 'verbal_communication', name: 'Verbal Communication', description: 'Verbal communication' },
            { key: 'supporting_teammates', name: 'Supporting Teammates', description: 'On and off the ball' }
        ]
    },
    {
        name: 'Coachability & Attitude',
        key: 'coachability',
        subcategories: [
            { key: 'takes_feedback', name: 'Takes Feedback', description: 'Takes feedback seriously' },
            { key: 'work_ethic', name: 'Work Ethic', description: 'Work ethic, drive, respect' }
        ]
    }
];

/**
 * Format date for display
 */
export function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

/**
 * Format subcategory name for display
 */
export function formatSubcategoryName(subcategoryKey) {
    return subcategoryKey
        .replace(/_/g, ' ')
        .split(' ')
        .map(w => w.charAt(0).toUpperCase() + w.slice(1))
        .join(' ');
}

/**
 * Calculate age from birth date
 */
export function calculateAge(birthDate) {
    const birth = new Date(birthDate);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    
    return age;
} 
