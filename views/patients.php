    <div class="row g-3 mb-3">
        <div class="col-md-5">
            <input
                type="text"
                id="searchIndex"
                class="form-control form-control-lg"
                placeholder="Numéro de dossier"
            >
        </div>

        <div class="col-md-5">
            <input
                type="text"
                id="searchPhone"
                class="form-control form-control-lg"
                placeholder="Téléphone (avec ou sans indicatif)"
            >
        </div>

        <div class="col-md-2 d-grid">
            <button class="btn btn-primary btn-lg" id="searchPatientBtn">
                Rechercher
            </button>
        </div>
    </div>

    <div id="patientResult"></div>


    <!-- ======================
 MODAL PATIENT (EDIT)
====================== -->
<div class="modal fade" id="editPatientModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Informations du patient</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="patientForm">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="numeroDossierPatient" id="numeroDossierPatient">

        <div class="modal-body">

          <!-- ================= INFOS PATIENT ================= -->
          <h6 class="fw-bold text-muted mb-2">Informations du patient</h6>

          <div class="row g-3">
            <div class="col-md-4">
              <label>Prénom</label>
              <input type="text" name="prenomPatient" id="prenomPatient" class="form-control">
            </div>

            <div class="col-md-4">
              <label>Nom</label>
              <input type="text" name="nomPatient" id="nomPatient" class="form-control">
            </div>

            <div class="col-md-2">
              <label>Sexe</label>
              <select name="sexe" id="sexe" class="form-select">
                <option value="">--</option>
                <option value="M">Masculin</option>
                <option value="F">Féminin</option>
              </select>
            </div>

            <div class="col-md-2">
              <label>Date de naissance</label>
              <input type="date" name="dateNaissance" id="dateNaissance" class="form-control">
            </div>

            <div class="col-md-2">
              <label>Âge</label>
              <input type="number" name="age" id="age" class="form-control" min="0"
>

            </div>

            <div class="col-md-4">
              <label>Email</label>
              <input type="email" name="email" id="email" class="form-control">
            </div>

            <div class="col-md-4">
              <label>Nationalité</label>
              <input type="text" name="nationalite" id="nationalite" class="form-control">
            </div>

            <div class="col-md-4">
                <label>Groupe sanguin</label>
                <input type="text" name="groupeSanguin" id="groupeSanguin" class="form-control">
            </div>

            <div class="col-md-4">
                <label>Identité officielle</label>
                <input type="text" name="identiteOfficielle" id="identiteOfficielle" class="form-control">
            </div>

          </div>

          <hr>

          <!-- ================= COORDONNÉES ================= -->
          <h6 class="fw-bold text-muted mb-2">Coordonnées</h6>

          <div class="row g-3">
            <div class="col-md-6">
              <label>Téléphone</label>
              <input type="tel" name="telephonePatient" id="telephonePatient" class="form-control">
            </div>

            <div class="col-md-6">
              <label>Adresse</label>
              <input type="text" name="adresse" id="adresse" class="form-control">
            </div>
          </div>

          <hr>

          <!-- ================= CONTACT URGENCE ================= -->
          <h6 class="fw-bold text-muted mb-2">Contact d’urgence</h6>

          <div class="row g-3">
            <div class="col-md-6">
              <label>Nom du contact</label>
              <input type="text" name="urgenceNom" id="urgenceNom" class="form-control">
            </div>

            <div class="col-md-6">
              <label>Téléphone du contact</label>
            
                <input type="tel" name="urgenceTelephone" id="urgenceTelephone" class="form-control">
            </div>
          </div>

        </div>

        <div id="patientAlert" class="alert d-none" role="alert"></div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
      </form>

    </div>
  </div>
</div>



<link rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">

<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js"></script>


    <script>

        const result     = document.getElementById('patientResult');
        const editPatientModal = document.getElementById('editPatientModal');
        const numeroDossierPatient = document.getElementById('numeroDossierPatient');
        const prenomPatient = document.getElementById('prenomPatient');
        const nomPatient = document.getElementById('nomPatient');
        const sexe = document.getElementById('sexe');
        const dateNaissance = document.getElementById('dateNaissance');
        const email = document.getElementById('email');
        const nationalite = document.getElementById('nationalite');
        const adresse = document.getElementById('adresse');
        const telephonePatient = document.getElementById('telephonePatient');
        const age = document.getElementById('age');
        const urgenceNom = document.getElementById('urgenceNom');
        const urgenceTelephone = document.getElementById('urgenceTelephone');
        const groupeSanguin = document.getElementById('groupeSanguin');
        const identiteOfficielle = document.getElementById('identiteOfficielle');


    document.getElementById('searchPatientBtn').addEventListener('click', () => {

        const indexInput = document.getElementById('searchIndex').value.trim();
        const phoneInput = document.getElementById('searchPhone').value.trim();

        result.innerHTML = '';

        let params = new URLSearchParams();

        if (indexInput !== '') {
            params.append('index', indexInput);
        } 
        else if (phoneInput !== '') {
            const cleanPhone = phoneInput.replace(/\D/g, '');

            if (cleanPhone.length < 9) {
                result.innerHTML =
                    '<div class="alert alert-warning">Le téléphone doit contenir au moins 9 chiffres.</div>';
                return;
            }

            params.append('phone', cleanPhone);
        }
        else {
            result.innerHTML =
                '<div class="alert alert-warning">Veuillez remplir un champ.</div>';
            return;
        }

        params.append('action', 'search');
        fetch('../Controller/patientController.php?' + params.toString())

            .then(r => r.json())
            .then(data => {

                if (data.status !== 'success') {
                    result.innerHTML =
                        '<div class="alert alert-danger">Aucun patient trouvé.</div>';
                    return;
                }

                const p = data.patient;


                let html = `
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5>${p.prenomPatient} ${p.nomPatient}</h5>
                                    <p><strong>Dossier :</strong> ${p.numeroDossierPatient}</p>
                                    <p><strong>Téléphone :</strong> ${formatPhone(p.telephonePatient)}</p>
                                    <p class="text-muted">
                                        <strong>Total RDV :</strong> ${data.rdvsCount}
                                    </p>
                                </div>

                                <button class="btn btn-outline-primary"
                                        onclick="openEditPatient('${p.numeroDossierPatient}')">
                                    Modifier
                                </button>

                            </div>
                        </div>
                    </div>
                `;

                //  LA LIGNE QUI MANQUAIT
                result.innerHTML = html;
            })
            .catch(() => {
                result.innerHTML =
                    '<div class="alert alert-danger">Erreur réseau</div>';
            });

    });

    document.getElementById('searchIndex').addEventListener('input', e => {
        document.getElementById('searchPhone').disabled = e.target.value !== '';
    });

    document.getElementById('searchPhone').addEventListener('input', e => {
        document.getElementById('searchIndex').disabled = e.target.value !== '';
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            document.getElementById('searchPatientBtn').click();
        }
    });

    document.getElementById('searchIndex').value = '';
    document.getElementById('searchPhone').value = '';

    function formatPhone(phone) {
        if (!phone) return '';
        return phone.replace(/^221/, '+221 ');
    }

    function toggleSearchBtn() {
        const index = document.getElementById('searchIndex').value.trim();
        const phone = document.getElementById('searchPhone').value.trim();

        document.getElementById('searchPatientBtn').disabled =
            index === '' && phone === '';
    }

    document.getElementById('searchIndex').addEventListener('input', toggleSearchBtn);
    document.getElementById('searchPhone').addEventListener('input', toggleSearchBtn);
    toggleSearchBtn();


    // ================= MODAL =================
function openEditPatient(numero) {
  fetch('../Controller/patientController.php?action=get&numero=' + numero)
    .then(r => r.json())
    .then(res => {
      if (res.status !== 'success') {
        alert('Patient introuvable');
        return;
      }

      const p = res.patient;

      numeroDossierPatient.value = p.numeroDossierPatient ?? '';
      prenomPatient.value = p.prenomPatient ?? '';
      nomPatient.value = p.nomPatient ?? '';
      sexe.value = p.sexe ?? '';
      age.value = p.age ?? '';
      email.value = p.email ?? '';
      nationalite.value = p.nationalite ?? '';
      groupeSanguin.value = p.groupeSanguin ?? '';
      identiteOfficielle.value = p.identiteOfficielle ?? '';
      adresse.value = p.adresse ?? '';

      itiPatient.setNumber(p.telephonePatient ?? '');
      urgenceNom.value = p.urgenceNom ?? '';
      itiUrgence.setNumber(p.urgenceTelephone ?? '');

      new bootstrap.Modal(editPatientModal).show();
    })
    .catch(err => {
      console.error(err);
      alert('Erreur chargement patient');
    });
}





    document.getElementById('patientForm').addEventListener('submit', e => {
  e.preventDefault();

  telephonePatient.value = itiPatient.getNumber();
  urgenceTelephone.value =
    urgenceTelephone.value.trim() !== ''
      ? itiUrgence.getNumber()
      : '';

  const data = new FormData(e.target);
  const alertBox = document.getElementById('patientAlert');

  fetch('../Controller/patientController.php', {
    method: 'POST',
    body: data
  })
  .then(r => r.json())
  .then(res => {

    alertBox.classList.remove('d-none');

    if (res.status === 'success') {
      alertBox.className = 'alert alert-success';
      alertBox.textContent = '✅ Modification enregistrée avec succès';

      // fermer le modal après 1.2s
      setTimeout(() => {
        bootstrap.Modal.getInstance(editPatientModal).hide();
      }, 1200);

    } else {
      alertBox.className = 'alert alert-danger';
      alertBox.textContent = '❌ Erreur lors de la modification';
    }
  })
  .catch(() => {
    alertBox.classList.remove('d-none');
    alertBox.className = 'alert alert-danger';
    alertBox.textContent = '❌ Erreur réseau';
  });
});



    let itiPatient, itiUrgence;

    document.addEventListener('DOMContentLoaded', () => {
        itiPatient = intlTelInput(telephonePatient, {
            initialCountry: "sn",
            separateDialCode: true,
            utilsScript:
            "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js"
        });

        itiUrgence = intlTelInput(urgenceTelephone, {
            initialCountry: "sn",
            separateDialCode: true,
            utilsScript:
            "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js"
        });
    });



</script>


