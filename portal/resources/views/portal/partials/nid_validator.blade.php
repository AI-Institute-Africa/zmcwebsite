@once
@push('scripts')
<script>
window.zmcFormatNID = function(value) {
  let raw = value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
  if (raw.length <= 2) return raw;
  return raw.substring(0, 2) + '-' + raw.substring(2);
};

window.zmcValidateNID = function(value) {
  if (!value || typeof value !== 'string') return { valid: false, formatted: '', message: 'ID number is required' };

  const trimmed = value.trim();

  const zwPattern = /^(\d{2})[\s\-]?(\d+)[\s\-]?([A-Za-z])[\s\-]?(\d{2})$/;
  const match = trimmed.match(zwPattern);
  if (match) {
    const formatted = `${match[1]}-${match[2]}${match[3].toUpperCase()}${match[4]}`;
    return { valid: true, formatted, type: 'national_id', message: 'Valid Zimbabwe National ID' };
  }

  const passportPattern = /^[A-Za-z]{1,2}\d{6,8}$/;
  if (passportPattern.test(trimmed)) {
    return { valid: true, formatted: trimmed.toUpperCase(), type: 'passport', message: 'Valid passport number format' };
  }

  if (/^[A-Za-z0-9\-\s]{5,20}$/.test(trimmed)) {
    return { valid: true, formatted: trimmed, type: 'other_id', message: 'Accepted ID format' };
  }

  return { valid: false, formatted: trimmed, message: 'Invalid ID format. Expected format: 08-2048701657V47' };
};

window.zmcAttachNIDValidation = function(inputSelector) {
  document.querySelectorAll(inputSelector).forEach(input => {
    if (input.dataset.nidBound) return;
    input.dataset.nidBound = '1';

    input.setAttribute('placeholder', '08-2048701657V47');

    input.addEventListener('input', function() {
      const pos = this.selectionStart;
      const oldLen = this.value.length;
      this.value = window.zmcFormatNID(this.value);
      const newLen = this.value.length;
      const newPos = pos + (newLen - oldLen);
      this.setSelectionRange(newPos, newPos);
    });

    const feedback = document.createElement('div');
    feedback.style.cssText = 'font-size:11px; margin-top:2px;';
    input.parentElement.appendChild(feedback);

    input.addEventListener('blur', function() {
      if (!this.value.trim()) {
        feedback.textContent = '';
        feedback.className = '';
        this.classList.remove('is-valid', 'is-invalid');
        return;
      }
      const result = window.zmcValidateNID(this.value);
      if (result.valid) {
        this.value = result.formatted;
        feedback.textContent = result.message;
        feedback.className = 'text-success';
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      } else {
        feedback.textContent = result.message;
        feedback.className = 'text-danger';
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
      }
    });
  });
};

document.addEventListener('DOMContentLoaded', function() {
  zmcAttachNIDValidation('.zmc-nid-input');
  zmcAttachNIDValidation('input[name="id_number"]');
  zmcAttachNIDValidation('input[name="national_id"]');
  zmcAttachNIDValidation('input[name="renewal_id_number"]');
  zmcAttachNIDValidation('input[name="profile[national_id]"]');
});
</script>
@endpush
@endonce
