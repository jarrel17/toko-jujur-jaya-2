// ANIMASI CARD
const fadeElements = document.querySelectorAll(".fade-in");

window.addEventListener("load", () => {
  fadeElements.forEach((el, index) => {
    setTimeout(() => {
      el.classList.add("show");
    }, index * 150);
  });
});

// JAM REALTIME

function updateTime() {
  const sekarang = new Date();

  const options = {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  };

  document.getElementById("tanggalRealtime").innerHTML =
    sekarang.toLocaleDateString("id-ID", options);
}

setInterval(updateTime, 1000);

updateTime();
