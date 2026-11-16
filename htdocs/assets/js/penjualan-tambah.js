/* form penjualan: tambah baris produk & kolom nama pembeli (kredit) */
(function () {

    var tombol  = document.getElementById('btn-tambah-produk');
    var area    = document.getElementById('produk-area');
    var metode  = document.getElementById('metode');
    var grup    = document.getElementById('grup-pembeli');
    var pembeli = document.getElementById('nama_pembeli');

    /* gandakan baris produk pertama (tanpa menyisipkan data database ke dalam JS) */
    if (tombol && area) {
        tombol.addEventListener('click', function () {

            var baris = area.querySelector('.row-item').cloneNode(true);

            baris.querySelector('input').value = '';
            baris.querySelector('select').selectedIndex = 0;

            area.appendChild(baris);
        });
    }

    /* kolom nama pembeli hanya muncul bila metode Kredit */
    function aturPembeli() {

        var kredit = metode.value === 'Kredit';

        grup.classList.toggle('sembunyi', !kredit);
        pembeli.required = kredit;
    }

    if (metode && grup && pembeli) {
        metode.addEventListener('change', aturPembeli);
        aturPembeli();
    }

})();
