var sidebar = document.getElementById('column-left');
var content = document.getElementById('content');
var showMenu = document.getElementById('button-menu');
var closeMenu = document.getElementById('button-menu-close');

showMenu.addEventListener('click', function() {
    sidebar.classList.toggle("show");
    closeMenu.classList.toggle("d-block");
    this.classList.toggle('d-none');
});

closeMenu.addEventListener('click', function() {
    sidebar.classList.toggle("show");
    this.classList.toggle("d-block");
    showMenu.classList.toggle('d-none');
});

window.addEventListener('resize', function() {
    sidebar.classList.remove("show");
    closeMenu.classList.remove("d-block");
    showMenu.classList.remove('d-none');
});
