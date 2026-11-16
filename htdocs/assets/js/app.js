/* Handler umum (pengganti atribut onclick/onsubmit inline, agar CSP tanpa 'unsafe-inline')
   - form[data-konfirmasi="pesan"]  : minta konfirmasi sebelum submit
   - [data-cetak]                    : cetak halaman
   - [data-kembali]                  : kembali ke halaman sebelumnya */

document.addEventListener('submit', function (e) {

    var form = e.target;

    if (form && form.hasAttribute && form.hasAttribute('data-konfirmasi')) {
        if (!window.confirm(form.getAttribute('data-konfirmasi'))) {
            e.preventDefault();
        }
    }
});

document.addEventListener('click', function (e) {

    var cetak = e.target.closest('[data-cetak]');

    if (cetak) {
        e.preventDefault();
        window.print();
        return;
    }

    var kembali = e.target.closest('[data-kembali]');

    if (kembali) {
        e.preventDefault();
        window.history.back();
    }
});
