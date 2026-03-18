/**
 * JW Finanças - Input Masks
 * Standalone mask library (no dependencies)
 */

'use strict';

/* ----------------------------------------------------------------
   CPF Mask + Validation
---------------------------------------------------------------- */

/**
 * Apply 000.000.000-00 mask to an input and validate the CPF.
 * @param {HTMLInputElement} input
 */
function cpfMask(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 11);

    if (v.length > 9) {
        v = v.replace(/^(\d{3})(\d{3})(\d{3})(\d{1,2})$/, '$1.$2.$3-$4');
    } else if (v.length > 6) {
        v = v.replace(/^(\d{3})(\d{3})(\d{1,3})$/, '$1.$2.$3');
    } else if (v.length > 3) {
        v = v.replace(/^(\d{3})(\d{1,3})$/, '$1.$2');
    }

    input.value = v;
}

/**
 * Validate Brazilian CPF (two-digit algorithm).
 * @param {string} cpf  Raw or formatted CPF string
 * @returns {boolean}
 */
function validateCpf(cpf) {
    const raw = cpf.replace(/\D/g, '');

    if (raw.length !== 11) return false;
    if (/^(\d)\1{10}$/.test(raw)) return false;

    let sum = 0;
    for (let i = 0; i < 9; i++) sum += parseInt(raw[i]) * (10 - i);
    let rem = sum % 11;
    const d1 = rem < 2 ? 0 : 11 - rem;
    if (parseInt(raw[9]) !== d1) return false;

    sum = 0;
    for (let i = 0; i < 10; i++) sum += parseInt(raw[i]) * (11 - i);
    rem = sum % 11;
    const d2 = rem < 2 ? 0 : 11 - rem;

    return parseInt(raw[10]) === d2;
}

/* ----------------------------------------------------------------
   Phone Mask (XX) XXXXX-XXXX
---------------------------------------------------------------- */

/**
 * @param {HTMLInputElement} input
 */
function phoneMask(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 11);

    if (v.length > 6) {
        // Mobile (11 digits): (11) 99999-9999
        if (v.length > 10) {
            v = v.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
        } else {
            // Landline (10 digits): (11) 9999-9999
            v = v.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
        }
    } else if (v.length > 2) {
        v = v.replace(/^(\d{2})(\d{0,5})$/, '($1) $2');
    } else if (v.length > 0) {
        v = v.replace(/^(\d{0,2})$/, '($1');
    }

    input.value = v;
}

/* ----------------------------------------------------------------
   Currency Mask R$ X.XXX,XX
---------------------------------------------------------------- */

/**
 * @param {HTMLInputElement} input
 */
function currencyMask(input) {
    let raw = input.value.replace(/\D/g, '');

    if (raw === '' || raw === '0') {
        input.value = '';
        return;
    }

    // Pad to at least 3 digits (e.g. 5 -> 005)
    raw = raw.padStart(3, '0');

    // Split cents
    const cents  = raw.slice(-2);
    const intPart = raw.slice(0, -2).replace(/^0+/, '') || '0';

    // Add thousand separators
    const formatted = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    input.value = formatted + ',' + cents;
}

/**
 * Parse a currency-masked string to float.
 * @param {string} v
 * @returns {number}
 */
function parseCurrency(v) {
    if (!v) return 0;
    return parseFloat(v.replace(/\./g, '').replace(',', '.')) || 0;
}

/* ----------------------------------------------------------------
   Date Mask DD/MM/YYYY
---------------------------------------------------------------- */

/**
 * @param {HTMLInputElement} input
 */
function dateMask(input) {
    let v = input.value.replace(/\D/g, '').slice(0, 8);

    if (v.length > 4) {
        v = v.replace(/^(\d{2})(\d{2})(\d{1,4})$/, '$1/$2/$3');
    } else if (v.length > 2) {
        v = v.replace(/^(\d{2})(\d{1,2})$/, '$1/$2');
    }

    input.value = v;
}

/**
 * Convert DD/MM/YYYY to YYYY-MM-DD (for input[type=date]).
 * @param {string} v
 * @returns {string}
 */
function dateToIso(v) {
    const parts = v.split('/');
    if (parts.length !== 3) return v;
    return `${parts[2]}-${parts[1]}-${parts[0]}`;
}
