const wrapper = document.querySelector('.wrapper');
const btnPopup = document.querySelector('.btnLogin-popup');
const iconClose = document.querySelector('.icon-close');
const form = document.querySelector('.form-box.login form'); 
const overlay = document.querySelector('.overlay'); // new

btnPopup.addEventListener('click', () => {
  wrapper.classList.add('active-popup');
  overlay.classList.add('active'); // show overlay
});

iconClose.addEventListener('click', () => {
  wrapper.classList.remove('active-popup');
  overlay.classList.remove('active'); // hide overlay
  form.reset();
});

overlay.addEventListener('click', () => {
  wrapper.classList.remove('active-popup');
  overlay.classList.remove('active');
  form.reset();
});