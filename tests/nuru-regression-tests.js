const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.resolve(__dirname, '..');

function createClassList() {
    const values = new Set();
    return {
        add: (...names) => names.forEach(name => values.add(name)),
        remove: (...names) => names.forEach(name => values.delete(name)),
        contains: name => values.has(name)
    };
}

function createElement() {
    return {
        classList: createClassList(),
        style: {},
        textContent: '',
        value: '',
        removeAttribute() {},
        setAttribute() {},
        addEventListener() {},
        querySelector() { return null; },
        closest() { return null; }
    };
}

function createStorage() {
    const data = new Map();
    return {
        getItem: key => data.has(key) ? data.get(key) : null,
        setItem: (key, value) => data.set(key, String(value)),
        removeItem: key => data.delete(key)
    };
}

function loadObject(relativeFile, exportExpression, contextOverrides = {}) {
    const elements = new Map([
        ['successModal', createElement()],
        ['applicationNumber', createElement()],
        ['applicationStatus', createElement()],
        ['applicationSubmittedAt', createElement()]
    ]);
    let modalShown = 0;
    const storage = createStorage();
    const context = {
        console: { log: console.log, warn: console.warn, error() {} },
        URL,
        setTimeout,
        clearTimeout,
        sessionStorage: storage,
        document: {
            readyState: 'complete',
            body: createElement(),
            addEventListener() {},
            getElementById: id => elements.get(id) || null,
            querySelector: () => null,
            querySelectorAll: () => []
        },
        window: {
            addEventListener() {},
            confirm: () => false,
            location: { assign() {} },
            bootstrap: {
                Modal: class {
                    show() { modalShown += 1; }
                }
            }
        },
        ...contextOverrides
    };
    context.globalThis = context;
    vm.createContext(context);
    const source = fs.readFileSync(path.join(root, relativeFile), 'utf8');
    vm.runInContext(`${source}\nglobalThis.__subject = ${exportExpression};`, context, { filename: relativeFile });
    return { context, elements, storage, subject: context.__subject, modalShown: () => modalShown };
}

function testSellerReceipt() {
    let formCleared = 0;
    let progressCleared = 0;
    const harness = loadObject(
        'html/material/seller/js/seller-form.js',
        'SellerForm',
        {
            FormDataUtils: { clearFormData: () => { formCleared += 1; } },
            FormStepper: { clearProgress: () => { progressCleared += 1; } }
        }
    );
    harness.subject.handleSubmissionSuccess({
        data: {
            application_number: 'SELL-TEST-001',
            status: 'submitted',
            submission_date: '2026-07-30 21:09:46'
        }
    });
    assert.equal(harness.elements.get('applicationNumber').textContent, 'SELL-TEST-001');
    assert.equal(harness.modalShown(), 1);
    assert.equal(formCleared, 1);
    assert.equal(progressCleared, 1);
    assert.match(harness.storage.getItem('nuru-seller-submission-receipt'), /SELL-TEST-001/);

    harness.elements.get('applicationNumber').textContent = '';
    harness.subject.restoreSubmissionReceipt();
    assert.equal(harness.elements.get('applicationNumber').textContent, 'SELL-TEST-001');
    assert.equal(harness.modalShown(), 2);

    harness.context.window.bootstrap.Modal = class {
        constructor() { throw new Error('presentation failure'); }
    };
    assert.doesNotThrow(() => harness.subject.showSubmissionReceipt({
        application_number: 'SELL-TEST-001',
        status: 'submitted',
        submitted_at: '2026-07-30T21:09:46'
    }));
    assert.equal(harness.elements.get('successModal').style.display, 'block');
}

function testBuyerReceipt() {
    const harness = loadObject('html/material/buyer/js/buyer-form.js', 'BuyerForm');
    harness.subject.trackSubmission = () => {};
    harness.subject.handleSubmissionSuccess({
        application_number: 'BUY-TEST-001',
        status: 'pending',
        submitted_at: '2026-07-30T21:09:46'
    });
    assert.equal(harness.elements.get('applicationNumber').textContent, 'BUY-TEST-001');
    assert.equal(harness.modalShown(), 1);
    assert.equal(harness.storage.getItem('nuru-buyer-form-data'), null);
    assert.match(harness.storage.getItem('nuru-buyer-submission-receipt'), /BUY-TEST-001/);

    harness.elements.get('applicationNumber').textContent = '';
    harness.subject.restoreSubmissionReceipt();
    assert.equal(harness.elements.get('applicationNumber').textContent, 'BUY-TEST-001');
    assert.equal(harness.modalShown(), 2);
}

function testAgentFinances() {
    const fields = new Map([
        ['gross_income', createElement()],
        ['total_deductions', createElement()],
        ['net_pay', createElement()]
    ]);
    const harness = loadObject(
        'html/material/agent/js/agent-form.js',
        'AgentForm',
        {
            document: {
                addEventListener() {},
                querySelectorAll: () => [],
                getElementById: id => fields.get(id) || null
            }
        }
    );
    const form = new harness.subject();
    const errors = new Map();
    form.showFieldError = (field, message) => errors.set(field, message);
    form.clearFieldError = field => errors.delete(field);

    fields.get('gross_income').value = '1000';
    fields.get('total_deductions').value = '1200';
    form.calculateNetPay();
    assert.equal(fields.get('net_pay').value, '-200.00');
    assert.equal(form.validateEmployment(), false);
    assert.match(errors.get(fields.get('total_deductions')), /cannot exceed gross income/);

    fields.get('total_deductions').value = '300';
    form.calculateNetPay();
    assert.equal(fields.get('net_pay').value, '700.00');
    assert.equal(form.validateEmployment(), true);
}

function testNamibianCurrencyLabel() {
    const source = fs.readFileSync(path.join(root, 'html/material/buyer/js/form-data.js'), 'utf8');
    const context = {};
    vm.createContext(context);
    vm.runInContext(`${source}\nglobalThis.__formatCurrency = formatCurrency;`, context, {
        filename: 'html/material/buyer/js/form-data.js'
    });

    assert.equal(context.__formatCurrency(800000), 'N$800,000.00');
    assert.equal(context.__formatCurrency('invalid'), 'N$0.00');
}

function testSellerFinalStepNavigationAndReset() {
    const elements = new Map([
        ['navigationButtons', createElement()],
        ['nextBtn', createElement()],
        ['prevBtn', createElement()],
        ['step-1', createElement()],
        ['step-9', createElement()]
    ]);
    elements.get('navigationButtons').classList.add('d-flex');
    elements.get('step-9').classList.add('active');

    const source = fs.readFileSync(path.join(root, 'html/material/seller/js/form-steps.js'), 'utf8');
    const context = {
        document: {
            addEventListener() {},
            getElementById: id => elements.get(id) || null
        },
        sessionStorage: createStorage(),
        localStorage: createStorage(),
        setTimeout,
        FormValidation: {}
    };
    vm.createContext(context);
    vm.runInContext(`${source}\nglobalThis.__formStepper = FormStepper;`, context, {
        filename: 'html/material/seller/js/form-steps.js'
    });

    const stepper = context.__formStepper;
    stepper.updateStepper = () => {};
    stepper.updateProgress = () => {};
    stepper.updateStepNumbers = () => {};
    stepper.currentStep = 9;
    stepper.updateNavigationVisibility();
    assert.equal(elements.get('navigationButtons').classList.contains('d-none'), true);

    stepper.clearProgress();
    assert.equal(stepper.currentStep, 1);
    assert.equal(elements.get('step-9').classList.contains('active'), false);
    assert.equal(elements.get('step-1').classList.contains('active'), true);
    assert.equal(elements.get('navigationButtons').classList.contains('d-none'), false);
}

function testPortalSecurityAndScopeRegressions() {
    const passwordPage = fs.readFileSync(path.join(root, 'html/material/change-password.php'), 'utf8');
    assert.match(passwordPage, /name="current_password"/);
    assert.match(passwordPage, /password_verify\(\$currentPassword, \$currentHash\)/);
    assert.match(passwordPage, /\$mustChangePassword/);

    for (const endpoint of [
        'approve_agent_application.php',
        'approve_buyer.php',
        'delete_agent.php',
        'delete_buyer.php',
        'mark_property_sold.php'
    ]) {
        const source = fs.readFileSync(path.join(root, 'html/material/config', endpoint), 'utf8');
        assert.match(source, /sessionHasAuthoritativeRole\(/, `${endpoint} must validate the live account state`);
    }

    const propertyForm = fs.readFileSync(path.join(root, 'html/material/property_admin_form.php'), 'utf8');
    assert.match(propertyForm, /scoped_allocation\.entity_reference = sa\.application_number/);

    const soldEndpoint = fs.readFileSync(path.join(root, 'html/material/config/mark_property_sold.php'), 'utf8');
    assert.match(soldEndpoint, /scoped_allocation\.entity_reference = scoped_seller\.application_number/);

    const documentViewer = fs.readFileSync(path.join(root, 'html/material/view_document.php'), 'utf8');
    assert.match(documentViewer, /ata\.entity_reference = sa\.application_number/);

    const buyerUpload = fs.readFileSync(path.join(root, 'html/material/api/applications/buyers/upload.php'), 'utf8');
    assert.match(buyerUpload, /sessionHasAuthoritativeRole\(/);
    assert.match(buyerUpload, /This buyer is not assigned to you/);

    const retiredAgentHandler = fs.readFileSync(path.join(root, 'html/material/agent/ajax_handler.php'), 'utf8');
    assert.match(retiredAgentHandler, /http_response_code\(410\)/);

    const loginPage = fs.readFileSync(path.join(root, 'html/material/authentication-login.php'), 'utf8');
    assert.match(loginPage, /data-callback="nuruTurnstileSuccess"/);
    assert.match(loginPage, /data-expired-callback="nuruTurnstileUnavailable"/);
    assert.match(loginPage, /TURNSTILE_ENABLED \? ' disabled aria-disabled="true"'/);

    for (const profile of ['buyers_profile.php', 'agent_profile.php']) {
        const source = fs.readFileSync(path.join(root, 'html/material', profile), 'utf8');
        assert.doesNotMatch(source, />Setting</);
        assert.doesNotMatch(source, /name="fake-password"/);
    }
}

testSellerReceipt();
testBuyerReceipt();
testAgentFinances();
testNamibianCurrencyLabel();
testSellerFinalStepNavigationAndReset();
testPortalSecurityAndScopeRegressions();
console.log('NURU regression tests passed');
