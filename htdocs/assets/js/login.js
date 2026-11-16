/* tombol "Lihat / Sembunyi" password di halaman login */
(function () {
    var tombol = document.querySelector('.toggle-pass');
    var isian  = document.getElementById('password');

    if (!tombol || !isian) {
        return;
    }

    tombol.addEventListener('click', function () {
        var tersembunyi = isian.type === 'password';

        isian.type = tersembunyi ? 'text' : 'password';
        tombol.textContent = tersembunyi ? 'Sembunyi' : 'Lihat';
    });
})();
