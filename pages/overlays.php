<div class="overlay" id="plantModal" onclick="closeModal(event)">
  <div class="modal">
    <button class="modal-close" onclick="closeModalDirect()">✕</button>
    <div id="modalBody"></div>
  </div>
</div>

<div class="confirm-dialog" id="confirmDialog">
  <div class="confirm-box">
    <div class="confirm-icon"  id="confirmIcon">⚠️</div>
    <div class="confirm-title" id="confirmTitle">Sei sicuro?</div>
    <div class="confirm-desc"  id="confirmDesc">Questa azione non può essere annullata.</div>
    <div class="confirm-actions">
      <button class="btn btn-ghost"  onclick="closeConfirm()">Annulla</button>
      <button class="btn btn-danger" id="confirmOkBtn" onclick="executeConfirm()">Elimina</button>
    </div>
  </div>
</div>

<div class="toast" id="toast">
  <span id="toastIcon">✓</span>
  <span id="toastMsg">—</span>
</div>
