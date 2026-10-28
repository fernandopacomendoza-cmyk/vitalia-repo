const toggle = document.querySelector('.menu-toggle');
const nav = document.querySelector('.main-nav');
toggle?.addEventListener('click', () => {
  const open = nav.classList.toggle('open');
  toggle.setAttribute('aria-expanded', String(open));
  toggle.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
  toggle.textContent = open ? '×' : '☰';
});
nav?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
  nav.classList.remove('open');
  toggle?.setAttribute('aria-expanded', 'false');
  if (toggle) { toggle.textContent = '☰'; toggle.setAttribute('aria-label', 'Abrir menú'); }
}));
const dateInput = document.querySelector('input[name="fecha"]');
if (dateInput) {
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  dateInput.min = tomorrow.toISOString().slice(0, 10);
}

const signUpButton = document.getElementById('signUpButton');
const signInButton = document.getElementById('signInButton');
const signInPanel = document.getElementById('signIn');
const signUpPanel = document.getElementById('signup');
signUpButton?.addEventListener('click', () => {
  if (signInPanel) signInPanel.hidden = true;
  if (signUpPanel) signUpPanel.hidden = false;
});
signInButton?.addEventListener('click', () => {
  if (signUpPanel) signUpPanel.hidden = true;
  if (signInPanel) signInPanel.hidden = false;
});

const servicePlace = document.getElementById('lugarServicio');
const serviceAddressLabel = document.getElementById('direccionServicioLabel');
const serviceAddress = document.getElementById('direccionServicio');
const coverageSelect = document.getElementById('coberturaServicio');
const insuranceFields = document.getElementById('seguroServicioFields');
const insurerInput = document.getElementById('aseguradoraServicio');
const policyInput = document.getElementById('polizaServicio');
servicePlace?.addEventListener('change', () => {
  const atHome = servicePlace.value === 'domicilio';
  if (serviceAddressLabel) serviceAddressLabel.hidden = !atHome;
  if (serviceAddress) serviceAddress.required = atHome;
});
coverageSelect?.addEventListener('change', () => {
  const insured = coverageSelect.value === 'seguro';
  if (insuranceFields) insuranceFields.hidden = !insured;
  if (insurerInput) insurerInput.required = insured;
  if (policyInput) policyInput.required = insured;
});

const appointmentCoverage = document.getElementById('appointmentCoverage');
const appointmentInsurance = document.getElementById('appointmentInsuranceFields');
const appointmentInsurer = document.getElementById('appointmentInsurer');
const appointmentPolicy = document.getElementById('appointmentPolicy');
appointmentCoverage?.addEventListener('change', () => {
  const insured = appointmentCoverage.value === 'seguro';
  if (appointmentInsurance) appointmentInsurance.hidden = !insured;
  if (appointmentInsurer) appointmentInsurer.required = insured;
  if (appointmentPolicy) appointmentPolicy.required = insured;
});

const appointmentSpecialty = document.getElementById('appointmentSpecialty');
const appointmentDoctor = document.getElementById('appointmentDoctor');
function filterAppointmentDoctors() {
  if (!appointmentSpecialty || !appointmentDoctor) return;
  const specialty = appointmentSpecialty.value;
  for (const option of appointmentDoctor.options) {
    const doctorSpecialty = option.dataset.specialty;
    option.hidden = Boolean(doctorSpecialty && specialty && doctorSpecialty !== specialty);
    if (option.selected && option.hidden) appointmentDoctor.value = '';
  }
}
appointmentSpecialty?.addEventListener('change', filterAppointmentDoctors);
filterAppointmentDoctors();
