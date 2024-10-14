const nama = "Sutan Navajo";
let usia = 1;

let biodata = document.getElementById('biodata');
console.log(biodata);

function generateBiodata() {
    let generasi;

    if(usia > 10 && usia < 18) {
        generasi = "generasi remaja";
    }
    else if (usia > 18 && usia < 30) {
        generasi = "generasi dewasa";
    }

    else if(usia >= 30) {
        generasi = "generasi lanjut";
    }
    else if(usia > 2 && usia < 10) {
        generasi = "generasi anka anak";
    }
    else {
        generasi = "generasi bayiii"

    }
    
    return biodata.innerHTML = generasi;
}

console.log(nama);
console.log(usia);

generateBiodata();