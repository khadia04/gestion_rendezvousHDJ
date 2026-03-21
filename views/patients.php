   
<?php
requireRole(['super_admin', 'admin', 'medecin', 'agent']);    
?>


    <div class="row g-3 mb-3">
        <div class="col-md-5">
            <input
                type="text"
                id="searchIndex"
                class="form-control form-control-lg"
                placeholder="Numéro de dossier"
                inputmode="numeric"
                pattern="[0-9]*"
            >
        </div>

        <div class="col-md-5">
            <input
                type="text"
                id="searchPhone"
                class="form-control form-control-lg"
                placeholder="Téléphone (avec ou sans indicatif)"
                inputmode="numeric"
                pattern="[0-9]*"
            >
        </div>

        <div class="col-md-2 d-grid">
            <button class="btn btn-primary btn-lg" id="searchPatientBtn">
                Rechercher
            </button>
        </div>
    </div>

    <div id="patientResult"></div>

    <div
      id="globalAlert"
      class="alert d-none position-fixed top-0 end-0 m-4 shadow"
      style="z-index: 2000;"
      role="alert">
    </div>



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
        <input type="hidden" name="numeroAuto" id="numeroAuto">


        <div class="modal-body">

          <!-- ================= INFOS PATIENT ================= -->
          <h6 class="fw-bold text-muted mb-2">Informations du patient</h6>

          <div class="row g-3">
            <div class="col-md-3">
              <label>Prénom</label>
              <input type="text" name="prenomPatient" id="prenomPatient" class="form-control">
            </div>

            <div class="col-md-3">
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
              <input type="number" name="age" id="age" class="form-control" min="0">

            </div>

            <div class="col-md-3">
              <label>Email</label>
              <input type="email" name="email" id="email" class="form-control">
            </div>

            <div class="col-md-3">
              <label>Nationalité</label>
              <input type="text" name="nationalite" id="nationalite" class="form-control">
            </div>

            <div class="col-md-3">
              <label>Groupe sanguin</label>
              <select name="groupeSanguin" id="groupeSanguin" class="form-select">
                <option value="">-- Sélectionner --</option>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B-">B-</option>
                <option value="AB+">AB+</option>
                <option value="AB-">AB-</option>
                <option value="O+">O+</option>
                <option value="O-">O-</option>
              </select>
            </div>


            <div class="col-md-3">
                <label>Identité officielle</label>
                <input type="text" name="identiteOfficielle" id="identiteOfficielle" class="form-control">
            </div>

          </div>

          <hr>

          <!-- ================= COORDONNÉES ================= -->
          

          <div class="row">
            <div class="col-6">
              <h6 class="fw-bold text-muted mb-3">Coordonnées</h6>

              <div class="row g-3">

                <div class="col-md-6">
                  <label class="form-label">Téléphone</label>
                  <input type="tel" name="telephonePatient" id="telephonePatient" class="form-control">
                </div>

                <div class="col-md-6">
                  <label class="form-label">Adresse</label>
                  <input type="text" name="adresse" id="adresse" class="form-control">
                </div>

              </div>
            </div>
         
            <div class="col-6">
              <!-- ================= CONTACT URGENCE ================= -->
              <h6 class="fw-bold text-muted mb-3">Contact d’urgence</h6>

              <div class="row g-3">

                <div class="col-md-6">
                  <label class="form-label">Nom du contact</label>
                  <input type="text" name="urgenceNom" id="urgenceNom" class="form-control">
                </div>

                <div class="col-md-6">
                  <label class="form-label">Téléphone du contact</label>
                  <input type="tel" name="urgenceTelephone" id="urgenceTelephone" class="form-control">
                </div>

              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
      </form>

    </div>
  </div>
</div>


<!-- ======================
 MODAL HISTORIQUE RENDEZ VOUS Patient & (EDIT)
====================== -->
<div class="modal fade" id="patientRdvsModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Rendez-vous du patient</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="patientRdvsContent">
         Chargement...
      </div>

    </div>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">

<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js"></script>


    <script>

        const result     = document.getElementById('patientResult');
        const editPatientModal = document.getElementById('editPatientModal');
        const numeroDossierPatient = document.getElementById('numeroDossierPatient');
        const numeroAuto = document.getElementById('numeroAuto');
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

        let lastSearch = { type: null, value: null };



    document.getElementById('searchPatientBtn').addEventListener('click', () => {

        const indexInput = document.getElementById('searchIndex').value.trim();
        const phoneInput = document.getElementById('searchPhone').value.trim();

        result.innerHTML = '';
        

        let params = new URLSearchParams();

        if (indexInput !== '') {
          params.append('index', indexInput);

          // 🔐 mémoriser la recherche
          lastSearch = { type: 'index', value: indexInput };

      } 
      else if (phoneInput !== '') {
          const cleanPhone = phoneInput.replace(/\D/g, '');

          if (cleanPhone.length < 9) {
              result.innerHTML =
                  '<div class="alert alert-warning">Le téléphone doit contenir au moins 9 chiffres.</div>';
              return;
          }

          params.append('phone', cleanPhone);

          // 🔐 mémoriser la recherche
          lastSearch = { type: 'phone', value: cleanPhone };
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
                    <div class="card patient-card shadow-sm border-0 mb-4">
                      <div class="card-body">

                        <div class="d-flex justify-content-between align-items-start">

                          <!-- LEFT -->
                          <div>

                            <h4 class="fw-bold mb-1 text-primary">
                              <i class="bi bi-person-circle me-2"></i>
                              ${p.prenomPatient} ${p.nomPatient}
                            </h4>

                            <div class="d-flex flex-wrap gap-3 mt-3">

                              <div class="info-pill">
                                <i class="bi bi-folder2-open me-1"></i>
                                <strong>Dossier :</strong>
                                <span class="badge bg-primary ms-1">${p.numeroDossierPatient ?? '—'}</span>
                              </div>

                              <div class="info-pill">
                                <i class="bi bi-telephone me-1"></i>
                                <strong>Téléphone :</strong>
                                <span class="ms-1">${formatPhone(p.telephonePatient)}</span>
                              </div>

                              <div class="info-pill">
                                <i class="bi bi-calendar-check me-1"></i>
                                <strong>RDV :</strong>
                                <span class="badge bg-success ms-1">${data.rdvsCount}</span>
                              </div>

                            </div>

                          </div>

                          <!-- RIGHT ACTIONS -->
                          <div class="d-flex gap-2">

                            <button 
                              class="btn btn-outline-primary btn-icon"
                              data-bs-toggle="tooltip"
                              data-bs-title="Voir historique"
                              onclick="openPatientRdvs('${p.numeroDossierPatient ?? ''}','${p.numeroAuto ?? ''}')">
                              <i class="bi bi-clock-history"></i>
                            </button>

                            <button 
                              class="btn btn-primary btn-icon"
                              data-bs-toggle="tooltip"
                              data-bs-title="Modifier"
                              onclick="openEditPatient({
                                numero: ${p.numeroDossierPatient ? `'${p.numeroDossierPatient}'` : 'null'},
                                phone: '${p.telephonePatient}',
                                numeroAuto: ${p.numeroAuto ? `'${p.numeroAuto}'` : 'null'}
                              })">
                              <i class="bi bi-pencil-square"></i>
                            </button>

                          </div>

                        </div>

                      </div>
                    </div>
                `;

                //  LA LIGNE QUI MANQUAIT
                result.innerHTML = `<div class="fade-in">${html}</div>`;
                initTooltips();
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
function openEditPatient(params) {

  let query = new URLSearchParams();
  query.append('action', 'get');

  if (params.numero) {
    query.append('numero', params.numero);
  } else if (params.numeroAuto) {
    query.append('numeroAuto', params.numeroAuto);
  } else {
    query.append('phone', params.phone);
  }

  fetch('../Controller/patientController.php?' + query.toString())
    .then(r => r.json())
    .then(res => {
      console.log('GET PATIENT →', res);

      if (res.status !== 'success') {
        alert('Patient introuvable');
        return;
      }

      const p = res.patient;

      numeroAuto.value = p.numeroAuto ?? '';
      numeroDossierPatient.value = p.numeroDossierPatient ?? '';

      prenomPatient.value = p.prenomPatient ?? '';
      nomPatient.value    = p.nomPatient ?? '';
      sexe.value          = p.sexe ?? '';
      dateNaissance.value = p.dateNaissance ?? '';
      age.value           = p.age ?? '';

      email.value = p.email ?? p.emailPatient ?? '';
      nationalite.value  = p.nationalite ?? '';
      groupeSanguin.value = p.groupeSanguin ?? p.groupe_sanguin ?? '';
      identiteOfficielle.value = p.identiteOfficielle ?? '';
      adresse.value      = p.adresse ?? '';
      urgenceNom.value   = p.urgenceNom ?? '';

      itiPatient.setNumber(p.telephonePatient ?? '');
      itiUrgence.setNumber(p.urgenceTelephone ?? '');

      
      new bootstrap.Modal(editPatientModal).show();
    })
    .catch(err => {
      console.error('Erreur fetch get patient', err);
      alert('Erreur réseau');
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

  fetch('../Controller/patientController.php', {
    method: 'POST',
    body: data
  })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {

        const numero = numeroDossierPatient.value;

        // fermer le modal
        bootstrap.Modal.getInstance(editPatientModal).hide();

        // rafraîchir la carte
        refreshPatientCard();


        // alerte après fermeture
        setTimeout(() => {
          showGlobalAlert(
            '✅ Informations du patient mises à jour',
            'success',
            5000
          );
        }, 300);

      } else {
        showGlobalAlert(
          '❌ Erreur lors de la modification du patient',
          'danger',
          5000
        );
      }
    })
    .catch(() => {
      showGlobalAlert(
        '❌ Erreur réseau',
        'danger',
        5000
      );
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

function showGlobalAlert(message, type = 'success', duration = 5000) {
  const alertBox = document.getElementById('globalAlert');

  alertBox.className = `alert alert-${type} position-fixed top-0 end-0 m-4 shadow`;
  alertBox.textContent = message;
  alertBox.classList.remove('d-none');

  setTimeout(() => {
    alertBox.classList.add('d-none');
  }, duration);
}

function refreshPatientCard() {
  if (!lastSearch.type) return;

  const params = new URLSearchParams();
  params.append('action', 'search');

  if (lastSearch.type === 'index') {
    params.append('index', lastSearch.value);
  } else if (lastSearch.type === 'phone') {
    params.append('phone', lastSearch.value);
  }

  fetch('../Controller/patientController.php?' + params.toString())
    .then(r => r.json())
    .then(data => {
      if (data.status !== 'success') return;

      const p = data.patient;

      let html = `
        <div class="card patient-card shadow-sm border-0 mb-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">

              <div>
                <h4 class="fw-bold mb-1 text-primary">
                  <i class="bi bi-person-circle me-2"></i>
                  ${p.prenomPatient} ${p.nomPatient}
                </h4>

                <div class="d-flex flex-wrap gap-3 mt-3">

                  <div class="info-pill">
                    <i class="bi bi-folder2-open me-1"></i>
                    <strong>Dossier :</strong>
                    <span class="badge bg-primary ms-1">${p.numeroDossierPatient ?? '—'}</span>
                  </div>

                  <div class="info-pill">
                    <i class="bi bi-telephone me-1"></i>
                    <strong>Téléphone :</strong>
                    <span class="ms-1">${formatPhone(p.telephonePatient)}</span>
                  </div>

                  <div class="info-pill">
                    <i class="bi bi-calendar-check me-1"></i>
                    <strong>RDV :</strong>
                    <span class="badge bg-success ms-1">${data.rdvsCount}</span>
                  </div>

                </div>
              </div>

              <div class="d-flex gap-2">
                <button 
                  class="btn btn-outline-primary btn-icon"
                  data-bs-toggle="tooltip"
                  data-bs-title="Voir historique"
                  onclick="openPatientRdvs('${p.numeroDossierPatient ?? ''}','${p.numeroAuto ?? ''}')">
                  <i class="bi bi-clock-history"></i>
                </button>

                <button 
                  class="btn btn-primary btn-icon"
                  data-bs-toggle="tooltip"
                  data-bs-title="Modifier"
                  onclick="openEditPatient({
                    numero: ${p.numeroDossierPatient ? `'${p.numeroDossierPatient}'` : 'null'},
                    phone: '${p.telephonePatient}',
                    numeroAuto: ${p.numeroAuto ? `'${p.numeroAuto}'` : 'null'}
                  })">
                  <i class="bi bi-pencil-square"></i>
                </button>
              </div>

            </div>
          </div>
        </div>
      `;

      result.innerHTML = `<div class="fade-in">${html}</div>`;
      initTooltips();
    });
}

function allowOnlyDigits(input) {
  input.addEventListener('input', () => {
    input.value = input.value.replace(/\D/g, '');
  });
}

// appliquer aux champs
allowOnlyDigits(document.getElementById('searchIndex'));
allowOnlyDigits(document.getElementById('searchPhone'));

function openPatientRdvs(numero, numeroAuto) {

   const modal = new bootstrap.Modal(
      document.getElementById('patientRdvsModal')
   );

   document.getElementById('patientRdvsContent').innerHTML = "Chargement...";
   modal.show(); // IMPORTANT : on ouvre AVANT le fetch

   fetch(`../Controller/patientController.php?action=getRdvs&numero=${numero}`)
   .then(r=>r.text()) // TEMPORAIRE POUR DEBUG
   .then(txt=>{

      console.log("RESPONSE:", txt);

      let res;
      try{
        res = JSON.parse(txt);
      }catch(e){
        document.getElementById('patientRdvsContent').innerHTML =
          '<div class="alert alert-danger">Erreur JSON controller</div>';
        return;
      }

      if(res.status !== 'success'){
         document.getElementById('patientRdvsContent').innerHTML =
           '<div class="alert alert-warning">Aucun rendez-vous</div>';
         return;
      }

      // ================= SEPARATION =================
      let todayRdvs = [];
      let waitingRdvs = [];
      let expiredRdvs = [];

      res.rdvs.forEach(rdv=>{
          if(rdv.statut === 'programme_du_jour'){
              todayRdvs.push(rdv);
          }
          else if(rdv.statut === 'depasse'){
              expiredRdvs.push(rdv);
          }
          else{
              waitingRdvs.push(rdv);
          }
      });

      // ================= FONCTION LIGNE =================
      function buildRow(rdv, isExpired = false){

          let badge = '';
          let actionBtn = '';

          // ===== STATUT =====
          if(rdv.statut === 'programme_du_jour'){
              badge = '<span class="badge bg-primary">Aujourd’hui</span>';
          }
          else if(rdv.statut === 'depasse'){
              badge = '<span class="badge bg-secondary">Dépassé</span>';
          }
          else{
              if(rdv.diff_jours == 1){
                badge = '<span class="badge bg-info">Demain</span>';
              }
              else{
                badge = `<span class="badge bg-warning">Dans ${rdv.diff_jours} jours</span>`;
              }
          }

          // ================= BOUTON MODIFIER =================
          if(!isExpired){
              actionBtn = `
                <button 
                  class="btn btn-sm btn-primary"
                  onclick="redirectToEditRdv(
                    '${rdv.idRv}',
                    '${rdv.dateRvServ}',
                    '${rdv.codeService}',
                    '${numero}'
                  )">
                    Modifier
                </button>
              `;
          }

          return `
              <tr class="${isExpired ? 'rdv-expired' : ''}">
                <td>${rdv.dateRvServ}</td>
                <td>${rdv.designService}</td>
                <td>${badge}</td>
                <td class="text-center">${actionBtn}</td>
              </tr>
          `;
      }

      // ================= CONSTRUCTION TABLE =================
      let html = `
      <div style="max-height:500px; overflow-y:auto;">
      <table class="table table-hover">
      <thead>
      <tr>
      <th>Date</th>
      <th>Service</th>
      <th>Statut</th>
      <th>Action</th>
      </tr>
      </thead>
      <tbody>
      `;

      // AUJOURD’HUI
      todayRdvs.forEach(rdv=>{
          html += buildRow(rdv);
      });

      // EN ATTENTE
      waitingRdvs.forEach(rdv=>{
          html += buildRow(rdv);
      });

      // HISTORIQUE
      if(expiredRdvs.length > 0){

          html += `
              <tr>
                <td colspan="4" class="fw-bold text-muted pt-3">
                    Historique
                </td>
              </tr>
          `;

          expiredRdvs.forEach(rdv=>{
              html += buildRow(rdv, true);
          });
      }

      html += `</tbody></table>`;

      document.getElementById('patientRdvsContent').innerHTML = html;

   })
   .catch(err=>{
      console.error(err);
      document.getElementById('patientRdvsContent').innerHTML =
        '<div class="alert alert-danger">Erreur réseau</div>';
   });
}

function initTooltips() {
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.forEach(el => {
    new bootstrap.Tooltip(el);
  });
}

function editRdv(date, serviceLabel, codeService, dossier) {

    //  Ouvrir modal
    const modal = new bootstrap.Modal(
        document.getElementById('addRdvModal')
    );
    modal.show();

    //  Mettre patient type = index
    document.querySelector('[data-value="index"]').click();

    //  Injecter numéro dossier
    patientInput.value = dossier;
    patientInput.dispatchEvent(new Event('blur'));

    //  Trouver le service
    const serviceItem = document.querySelector(
      '#addRdvModal .service-item[data-code="' + codeService + '"]'
    );

    if (serviceItem) {

        //  Simuler un vrai clic utilisateur
        serviceItem.click();

        //  Positionner le bon mois après clic
        const d = new Date(date);
        currentDate = new Date(d.getFullYear(), d.getMonth(), 1);

        //  Recharger calendrier
        setTimeout(() => {

            loadCalendar();

            setTimeout(() => {

                document.querySelectorAll('.calendar-day').forEach(day => {

                    if (
                        parseInt(day.textContent) === d.getDate()
                    ) {
                        day.click();
                    }

                });

            }, 500);

        }, 300);

}

}


function redirectToEditRdv(idRv, date, codeService, dossier) {

    const params = new URLSearchParams();

    params.set('page', 'rendezvous');
    params.set('edit', 1);
    params.set('idRv', idRv);
    params.set('dossier', dossier);
    params.set('service', codeService);
    params.set('date', date);

    window.location.href = '?' + params.toString();
}
</script>


