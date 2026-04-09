'use strict';

/** ============================ Beam Analysis Data Type ============================ */

/**
 * Beam material specification.
 *
 * @param {String} name         Material name
 * @param {Object} properties   Material properties {EI : 0, GA : 0, ....}
 */
class Material {
    constructor(name, properties) {
        this.name = name;
        this.properties = properties;
    }
}

/**
 *
 * @param {Number} primarySpan          Beam primary span length
 * @param {Number} secondarySpan        Beam secondary span length
 * @param {Material} material           Beam material object
 */
class Beam {
    constructor(primarySpan, secondarySpan, material) {
        this.primarySpan = primarySpan;
        this.secondarySpan = secondarySpan;
        this.material = material;
    }
}

/** ============================ Beam Analysis Class ============================ */

class BeamAnalysis {
    constructor() {
        this.options = {
            condition: 'simply-supported'
        };

        this.analyzer = {
            'simply-supported': new BeamAnalysis.analyzer.simplySupported(),
            'two-span-unequal': new BeamAnalysis.analyzer.twoSpanUnequal()
        };
    }
    /**
     *
     * @param {Beam} beam
     * @param {Number} load
     */
    getDeflection(beam, load, condition) {
        var analyzer = this.analyzer[condition];

        if (analyzer) {
            return {
                beam: beam,
                load: load,
                equation: analyzer.getDeflectionEquation(beam, load)
            };
        } else {
            throw new Error('Invalid condition');
        }
    }
    getBendingMoment(beam, load, condition) {
        var analyzer = this.analyzer[condition];

        if (analyzer) {
            return {
                beam: beam,
                load: load,
                equation: analyzer.getBendingMomentEquation(beam, load)
            };
        } else {
            throw new Error('Invalid condition');
        }
    }
    getShearForce(beam, load, condition) {
        var analyzer = this.analyzer[condition];

        if (analyzer) {
            return {
                beam: beam,
                load: load,
                equation: analyzer.getShearForceEquation(beam, load)
            };
        } else {
            throw new Error('Invalid condition');
        }
    }
}




/** ============================ Beam Analysis Analyzer ============================ */

/**
 * Available analyzers for different conditions
 */
BeamAnalysis.analyzer = {};

/**
 * Calculate deflection, bending stress and shear stress for a simply supported beam
 *
 * @param {Beam}   beam   The beam object
 * @param {Number}  load    The applied load
 */
BeamAnalysis.analyzer.simplySupported = class {
    constructor(beam, load) {
        this.beam = beam;
        this.load = load;
    }
    getDeflectionEquation(beam, load) {
        let L = beam.primarySpan;
        let w = load;
        return function (x) {
            let EI = beam.material.properties.EI;
            let x_mm = x * 1000;
            let L_mm = L * 1000;
            let y = (-w * x_mm) / (24 * EI) * (Math.pow(L_mm, 3) - 2 * L_mm * Math.pow(x_mm, 2) + Math.pow(x_mm, 3));
            return { x: x, y: y };
        };
    }
    getBendingMomentEquation(beam, load) {
        let L = beam.primarySpan;
        let w = load;
        return function (x) {
            let y = (w * x / 2) * (L - x);
            return { x: x, y: y };
        };
    }
    getShearForceEquation(beam, load) {
        let L = beam.primarySpan;
        let w = load;
        return function (x) {
            let y = w * (L / 2 - x);
            return { x: x, y: y };
        };
    }
};


/**
 * Calculate deflection, bending stress and shear stress for a beam with two spans of equal condition
 *
 * @param {Beam}   beam   The beam object
 * @param {Number}  load    The applied load
 */
BeamAnalysis.analyzer.twoSpanUnequal = class {
    constructor(beam, load) {
        this.beam = beam;
        this.load = load;
    }
    
    getReactions(beam, load) {
        let w = load; 
        let L1 = beam.primarySpan;
        let L2 = beam.secondarySpan || L1;
        
        let MB = (-w * (Math.pow(L1, 3) + Math.pow(L2, 3))) / (8 * (L1 + L2));
        let RA = (w * L1 / 2) + (MB / L1);
        let RC = (w * L2 / 2) + (MB / L2);
        let RB_right = (w * L2 / 2) - (MB / L2);
        
        return { w: w, L1: L1, L2: L2, MB: MB, RA: RA, RC: RC, VB_right: RB_right };
    }

    getDeflectionEquation(beam, load) {
        let {w, L1, L2, MB, RA, RC, VB_right} = this.getReactions(beam, load);
        return function (x) {
            let EI = beam.material.properties.EI;
            let y = 0;
            let RA_N = RA * 1000;
            let VB_right_N = VB_right * 1000;
            let MB_Nmm = MB * 1000000;
            let L1_mm = L1 * 1000;
            let L2_mm = L2 * 1000;
            let w_Nmm = w;
            let x_mm = x * 1000;
            
            if (x <= L1) {
                let C1 = (w_Nmm * Math.pow(L1_mm, 3) / 24) - (RA_N * Math.pow(L1_mm, 2) / 6);
                y = (1 / EI) * (RA_N * Math.pow(x_mm, 3) / 6 - w_Nmm * Math.pow(x_mm, 4) / 24 + C1 * x_mm);
            } else {
                let u = x_mm - L1_mm;
                let D1 = - (MB_Nmm * L2_mm / 2) - (VB_right_N * Math.pow(L2_mm, 2) / 6) + (w_Nmm * Math.pow(L2_mm, 3) / 24);
                y = (1 / EI) * ((MB_Nmm * Math.pow(u, 2) / 2) + (VB_right_N * Math.pow(u, 3) / 6) - (w_Nmm * Math.pow(u, 4) / 24) + D1 * u);
            }
            return { x: x, y: y };
        };
    }
    getBendingMomentEquation(beam, load) {
        let {w, L1, L2, MB, RA, RC, VB_right} = this.getReactions(beam, load);
        return function (x) {
            let y = 0;
            if (x <= L1) {
                y = (RA * x) - (w * Math.pow(x, 2) / 2);
            } else {
                let u = x - L1;
                y = MB + (VB_right * u) - (w * Math.pow(u, 2) / 2);
            }
            return { x: x, y: y };
        };
    }
    getShearForceEquation(beam, load) {
        let {w, L1, L2, MB, RA, RC, VB_right} = this.getReactions(beam, load);
        return function (x) {
            let y = 0;
            if (x <= L1) {
                y = RA - (w * x);
            } else {
                let u = x - L1;
                y = VB_right - (w * u);
            }
            return { x: x, y: y };
        };
    }
};
