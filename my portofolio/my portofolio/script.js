const navLinks = document.querySelectorAll('.nav-links a');

navLinks.forEach(link => {
    link.addEventListener('click', (event) => {
        event.preventDefault();
        const targetSection = document.querySelector(event.target.getAttribute('href'));
        targetSection.scrollIntoView({ behavior: 'smooth' });
    });
});

const btn = document.querySelector('.btn');
btn.addEventListener('mouseover', (event) =>{
    const x = event.pageX - btn.offsetLeft;
    const Y = event.pageY - btn.offsetTop;

    btn.style.setProperty('--x', x + "px");
    btn.style.setProperty('--y', y + "px");

})

function toggleDetails(sectionId) {
    const sections = document.querySelectorAll('.details-section');
    sections.forEach(section => {
        if (section.id === sectionId) {
            section.classList.toggle('active');
        } else {
            section.classList.remove('active');
        }
    });
}







