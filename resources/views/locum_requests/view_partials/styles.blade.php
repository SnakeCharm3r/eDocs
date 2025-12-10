<style>
  /* Ultra-wide modal size (bigger than Bootstrap's modal-xl) */
  .modal-dialog.modal-xxl {
    max-width: min(1280px, 95vw);   /* roomy but safe on small screens */
  }

  /* Keep header/footer fixed and make body scroll if content is long */
  .modal-dialog.modal-xxl .modal-content {
    max-height: 90vh;
    display: flex;
    flex-direction: column;
  }
  .modal-dialog.modal-xxl .modal-body {
    overflow: auto;
  }

  /* Optional: a touch more breathing room for dense tables */
  .modal-dialog.modal-xxl .table-sm th,
  .modal-dialog.modal-xxl .table-sm td {
    padding-top: .5rem;
    padding-bottom: .5rem;
  }
</style>
