/**
 * Formulaire de Contact Dynamique
 * 
 * Gère un processus de contact en 4 étapes avec validation des données,
 * upload de fichiers et soumission AJAX. Inclut la gestion des statuts clients
 * et des redirections conditionnelles.
 */

(function ($) {
    'use strict';

    // ===========================================
    // CONFIGURATION ET ÉTAT GLOBAL
    // ===========================================

    const ContactFormState = {
        currentStep: 1,
        totalSteps: 4,
        uploadedFile: null,
        clientStatus: null,
        isSubmitting: false
    };

    const ContactFormConfig = {
        init() {
            this.loadConfiguration();
        },

        loadConfiguration() {
            if (typeof hticContactConfig === 'undefined') {
                window.hticContactConfig = {
                    ajaxUrl: '/wp-admin/admin-ajax.php',
                    nonce: $('#contact-form input[name="nonce"]').val()
                };
            }
        }
    };

    // ===========================================
    // INITIALISATION PRINCIPALE
    // ===========================================

    $(document).ready(function () {
        ContactFormConfig.init();
        EventManager.init();
        ProgressManager.updateProgress();
    });

    // ===========================================
    // GESTION DES ÉVÉNEMENTS
    // ===========================================

    const EventManager = {
        init() {
            this.bindClientStatusEvents();
            this.bindNavigationEvents();
            this.bindValidationEvents();
            this.bindModalEvents();
            this.bindFileUploadEvents();
            this.bindFormSubmissionEvents();
            this.bindFieldFormatting();
        },

        bindClientStatusEvents() {
            $('input[name="client_status"]').on('change', function () {
                const status = $(this).val();
                ContactFormState.clientStatus = status;
                $('#btn-next-status').prop('disabled', false);
            });
        },

        bindNavigationEvents() {
            $('.btn-next').on('click', NavigationManager.handleNextStep);
            $('.btn-prev').on('click', NavigationManager.handlePrevStep);
        },

        bindValidationEvents() {
            $('.form-control').on('blur', ValidationManager.validateField);
        },

        bindModalEvents() {
            $('#show-account-modal, #show-general-account-info').on('click', function (e) {
                e.preventDefault();
                ModalManager.openModal('#account-modal');
            });

            $('.modal-close').on('click', ModalManager.closeModals);
            $('.modal-overlay').on('click', ModalManager.handleOverlayClick);

            $('#continue-form-anyway').on('click', function () {
                ModalManager.closeModals();
                NavigationManager.goToStep(2);
            });
        },

        bindFileUploadEvents() {
            FileManager.init();
        },

        bindFormSubmissionEvents() {
            $('#btn-submit-contact').on('click', function (e) {
                FormSubmissionManager.handleSubmit(e, $(this));
            });
            $('#contact-form').on('submit', function (e) {
                e.preventDefault();
                return false;
            });
        },

        bindFieldFormatting() {
            $('#telephone').on('input', FormattingManager.formatPhone);
            $('#code_postal').on('input', FormattingManager.formatPostalCode);
        }
    };

    // ===========================================
    // GESTION DE LA NAVIGATION
    // ===========================================

    const NavigationManager = {
        handleNextStep(e) {
            e.preventDefault();
            const $btn = $(this);
            let nextStep = parseInt($btn.data('next'));

            if (ContactFormState.currentStep === 1) {
                if (!$('input[name="client_status"]:checked').length) {
                    alert('Veuillez sélectionner votre statut');
                    return;
                }

                ContactFormState.clientStatus = $('input[name="client_status"]:checked').val();

                if (ContactFormState.clientStatus === 'is-client') {
                    ModalManager.openModal('#client-redirect-modal');
                    return;
                } else {
                    nextStep = 2;
                }
            }

            if (ValidationManager.validateCurrentStep()) {
                NavigationManager.goToStep(nextStep);
            }
        },

        handlePrevStep(e) {
            e.preventDefault();
            const prevStep = parseInt($(this).data('prev'));

            if (prevStep === 1) {
                $('#client-info-box').hide();
            }

            NavigationManager.goToStep(prevStep);
        },

        goToStep(step) {
            if (step < 1 || step > ContactFormState.totalSteps) return;

            $(`.form-step[data-step="${ContactFormState.currentStep}"]`).removeClass('active');
            $(`.form-step[data-step="${step}"]`).addClass('active');

            ProgressManager.updateStepIndicators(step);
            ContactFormState.currentStep = step;
            ProgressManager.updateProgress();

            if (step === 4) {
                SummaryManager.generate();
            }

            this.scrollToForm();
        },

        scrollToForm() {
            const $form = $('#htic-contact-form');
            if ($form.length) {
                $('html, body').animate({
                    scrollTop: $form.offset().top - 50
                }, 300);
            }
        }
    };

    // ===========================================
    // GESTION DE LA PROGRESSION
    // ===========================================

    const ProgressManager = {
        updateProgress() {
            const progressPercent = (ContactFormState.currentStep / ContactFormState.totalSteps) * 100;
            $('#progress-fill').css('width', progressPercent + '%');
        },

        updateStepIndicators(step) {
            $('.step').removeClass('active completed');

            for (let i = 1; i < step; i++) {
                $(`.step[data-step="${i}"]`).addClass('completed');
            }

            $(`.step[data-step="${step}"]`).addClass('active');
        }
    };

    // ===========================================
    // GESTION DES MODALES
    // ===========================================

    const ModalManager = {
        openModal(modalSelector) {
            $(modalSelector).fadeIn(300);
            $('body').addClass('modal-open');
        },

        closeModals() {
            $('#account-modal, #client-redirect-modal').fadeOut(300);
            $('body').removeClass('modal-open');
        },

        handleOverlayClick(e) {
            if (e.target === this) {
                ModalManager.closeModals();
            }
        }
    };

    // ===========================================
    // VALIDATION DES DONNÉES
    // ===========================================

    const ValidationManager = {
        validateCurrentStep() {
            let isValid = true;
            const currentStepElement = $(`.form-step[data-step="${ContactFormState.currentStep}"]`);

            currentStepElement.find('.error-message').text('');
            currentStepElement.find('.form-control').removeClass('error');

            if (ContactFormState.currentStep === 1) {
                return true;
            }

            currentStepElement.find('.form-control[required]').each(function () {
                if (!ValidationManager.validateField.call(this)) {
                    isValid = false;
                }
            });

            if (ContactFormState.currentStep === 4) {
                if (!$('#captcha-check').is(':checked')) {
                    this.showFieldError('#captcha-check', 'Veuillez confirmer que vous n\'êtes pas un robot');
                    isValid = false;
                }
                if (!$('#rgpd-consent').is(':checked')) {
                    this.showFieldError('#rgpd-consent', 'Vous devez accepter la politique de confidentialité');
                    isValid = false;
                }
            }

            return isValid;
        },

        validateField() {
            const $field = $(this);
            const value = $field.val().trim();
            const fieldType = $field.attr('type') || $field.prop('tagName').toLowerCase();
            let isValid = true;
            let errorMessage = '';

            $field.removeClass('error success');
            $field.siblings('.error-message').text('');

            if ($field.prop('required') && !value) {
                isValid = false;
                errorMessage = 'Ce champ est obligatoire';
            }
            else if (fieldType === 'email' && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Format d\'email invalide';
                }
            }
            else if ($field.attr('name') === 'telephone' && value) {
                const phoneClean = value.replace(/[\s\-\+\(\)\.]/g, '');
                if (phoneClean.length < 10) {
                    isValid = false;
                    errorMessage = 'Le téléphone doit contenir au moins 10 chiffres';
                }
            }
            else if ($field.attr('name') === 'code_postal' && value) {
                const postalRegex = /^[0-9]{5}$/;
                if (!postalRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Le code postal doit contenir 5 chiffres';
                }
            }

            if (!isValid) {
                ValidationManager.showFieldError($field, errorMessage);
            } else if (value) {
                $field.addClass('success');
            }

            return isValid;
        },

        showFieldError($field, message) {
            if (typeof $field === 'string') {
                $field = $($field);
            }
            $field.addClass('error');
            $field.siblings('.error-message').text(message);
        }
    };

    // ===========================================
    // FORMATAGE DES CHAMPS
    // ===========================================

    const FormattingManager = {
        formatPhone() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 0) {
                const parts = value.match(/.{1,2}/g);
                if (parts) {
                    this.value = parts.slice(0, 5).join(' ');
                }
            }
        },

        formatPostalCode() {
            this.value = this.value.replace(/\D/g, '').slice(0, 5);
        }
    };

    // ===========================================
    // GESTION DES FICHIERS
    // ===========================================

    const FileManager = {
        init() {
            const $uploadArea = $('#upload-area');
            const $fileInput = $('#file-input');

            if (!$uploadArea.length || !$fileInput.length) {
                return;
            }

            this.bindUploadEvents($uploadArea, $fileInput);
        },

        bindUploadEvents($uploadArea, $fileInput) {
            $uploadArea.on('click', function (e) {
                if (!$(e.target).hasClass('btn-remove-file')) {
                    $fileInput.click();
                }
            });

            $uploadArea.on('dragover dragenter', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });

            $uploadArea.on('dragleave dragend', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });

            $uploadArea.on('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');

                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    FileManager.handleFileSelection(files[0]);
                }
            });

            $fileInput.on('change', function () {
                if (this.files.length > 0) {
                    FileManager.handleFileSelection(this.files[0]);
                }
            });

            $(document).on('click', '.btn-remove-file', function (e) {
                e.stopPropagation();
                FileManager.resetUpload();
            });
        },

        handleFileSelection(file) {
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) {
                this.showUploadError('Le fichier est trop volumineux (max. 5 Mo)');
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf',
                'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

            if (!allowedTypes.includes(file.type)) {
                this.showUploadError('Type de fichier non autorisé. Formats acceptés : JPG, PNG, PDF, DOC, DOCX');
                return;
            }

            this.simulateUpload(file);
        },

        simulateUpload(file) {
            const $uploadArea = $('#upload-area');

            $uploadArea.find('.upload-content').hide();
            $uploadArea.find('.upload-progress').show();

            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 30;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                    FileManager.showUploadSuccess(file);
                }
                $('.upload-progress-fill').css('width', progress + '%');
            }, 200);

            ContactFormState.uploadedFile = file;
        },

        showUploadSuccess(file) {
            const $uploadArea = $('#upload-area');

            $uploadArea.find('.upload-progress').hide();
            $uploadArea.find('.upload-success').show();

            let fileName = file.name;
            if (fileName.length > 30) {
                fileName = fileName.substring(0, 27) + '...';
            }
            $uploadArea.find('.upload-success-text').text(fileName);
            $('#upload-area').siblings('.error-message').text('');
        },

        showUploadError(message) {
            $('#upload-area').siblings('.error-message').text(message);
            FileManager.resetUpload();
        },

        resetUpload() {
            const $uploadArea = $('#upload-area');

            $uploadArea.find('.upload-progress, .upload-success').hide();
            $uploadArea.find('.upload-content').show();
            $('.upload-progress-fill').css('width', '0%');

            $('#file-input').val('');
            ContactFormState.uploadedFile = null;
        }
    };

    // ===========================================
    // SOUMISSION DU FORMULAIRE
    // ===========================================

    const FormSubmissionManager = {
        handleSubmit(e, $btn) {
            e.preventDefault();
            e.stopPropagation();

            if (ContactFormState.isSubmitting) {
                return false;
            }

            if (!ValidationManager.validateCurrentStep()) {
                return false;
            }

            ContactFormState.isSubmitting = true;
            FormSubmissionManager.updateSubmitButton($btn, true);

            const formData = FormSubmissionManager.prepareFormData();
            FormSubmissionManager.sendFormData(formData, $btn);

            return false;
        },

        prepareFormData() {
            const formData = new FormData();

            formData.append('action', 'htic_process_contact');

            const nonce = this.getNonce();
            formData.append('nonce', nonce);

            const contactData = {
                clientStatus: ContactFormState.clientStatus,
                civilite: $('#civilite').val(),
                firstName: $('#prenom').val(),
                lastName: $('#nom').val(),
                email: $('#email').val(),
                phone: $('#telephone').val(),
                postalCode: $('#code_postal').val(),
                ville: $('#ville').val(),
                adresse: $('#adresse').val(),
                objet: $('#objet_demande').val(),
                message: $('#message').val(),
                type: 'contact',
                timestamp: new Date().toISOString(),
                source: 'website'
            };

            formData.append('form_data', JSON.stringify(contactData));

            if (ContactFormState.uploadedFile) {
                formData.append('fichier', ContactFormState.uploadedFile);
            }

            return formData;
        },

        getNonce() {
            return (typeof hticSimulateur !== 'undefined' && hticSimulateur.nonce)
                ? hticSimulateur.nonce
                : (typeof hticContactConfig !== 'undefined' && hticContactConfig.nonce)
                    ? hticContactConfig.nonce
                    : $('#contact-form input[name="nonce"]').val();
        },

        sendFormData(formData, $btn) {
            const ajaxUrl = (typeof hticSimulateur !== 'undefined' && hticSimulateur.ajaxUrl)
                ? hticSimulateur.ajaxUrl
                : '/wp-admin/admin-ajax.php';

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                timeout: 30000,
                success: (response) => {
                    if (response.success) {
                        MessageManager.show('Votre demande a été envoyée avec succès !', 'success');
                        setTimeout(() => {
                            FormResetManager.resetForm();
                            window.location.href = '/merci-contact';
                        }, 100);
                    } else {
                        const errorMsg = response.data || 'Une erreur est survenue lors de l\'envoi';
                        MessageManager.show(errorMsg, 'error');
                    }
                },
                error: (xhr, status, error) => {
                    MessageManager.show('Erreur de connexion. Veuillez réessayer.', 'error');
                },
                complete: () => {
                    FormSubmissionManager.updateSubmitButton($btn, false);
                    ContactFormState.isSubmitting = false;
                }
            });
        },

        updateSubmitButton($btn, isSubmitting) {
            if (isSubmitting) {
                const originalText = $btn.html();
                $btn.data('original-text', originalText);
                $btn.addClass('submitting loading').prop('disabled', true);
                $btn.html('<span class="spinner"></span> Envoi en cours...');
            } else {
                const originalText = $btn.data('original-text');
                $btn.removeClass('submitting loading disabled').prop('disabled', false);
                $btn.html(originalText);
            }
        }
    };

    // ===========================================
    // GESTION DES MESSAGES
    // ===========================================

    const MessageManager = {
        show(text, type) {
            let $messages = $('#form-messages');

            if (!$messages.length) {
                $messages = $('<div id="form-messages" class="form-messages"></div>');
                $('#contact-form').prepend($messages);
            }

            const messageClass = `message-${type}`;
            const icon = type === 'success' ? '✅' : '❌';

            $messages.html(`
                <div class="message ${messageClass}">
                    ${icon} ${text}
                </div>
            `).fadeIn();

            this.scrollToMessage($messages);

            if (type === 'success') {
                setTimeout(() => {
                    $messages.fadeOut();
                }, 10000);
            }
        },

        scrollToMessage($messages) {
            $('html, body').animate({
                scrollTop: $messages.offset().top - 100
            }, 300);
        }
    };

    // ===========================================
    // RÉINITIALISATION DU FORMULAIRE
    // ===========================================

    const FormResetManager = {
        resetForm() {
            ContactFormState.currentStep = 1;
            ContactFormState.clientStatus = null;
            ContactFormState.isSubmitting = false;

            $('#contact-form')[0].reset();
            FileManager.resetUpload();

            $('.error-message').text('');
            $('.form-control').removeClass('error success');
            $('#client-info-box').hide();
            $('#btn-next-status').prop('disabled', true);
        }
    };

    // ===========================================
    // GESTION DU RÉCAPITULATIF
    // ===========================================

    const SummaryManager = {
        generate() {
            const $summary = $('#form-summary');
            if (!$summary.length) return;

            const formData = this.collectFormData();
            const summaryHtml = this.buildSummaryHtml(formData);

            $summary.html(summaryHtml);
        },

        collectFormData() {
            return {
                statut_client: ContactFormState.clientStatus === 'is-client' ? 'Client existant' : 'Non client',
                civilite: $('#civilite').val(),
                nom: $('#nom').val(),
                prenom: $('#prenom').val(),
                email: $('#email').val(),
                telephone: $('#telephone').val(),
                adresse: $('#adresse').val(),
                code_postal: $('#code_postal').val(),
                ville: $('#ville').val(),
                objet_demande: $('#objet_demande').val(),
                message: $('#message').val()
            };
        },

        buildSummaryHtml(formData) {
            let html = `
                <div class="summary-section">
                    <h5>Statut</h5>
                    <div class="summary-item">
                        <span class="summary-label">Type de demande :</span>
                        <span class="summary-value">${formData.statut_client}</span>
                    </div>
                </div>
                
                <div class="summary-section">
                    <h5>Vos informations</h5>
                    <div class="summary-item">
                        <span class="summary-label">Nom complet :</span>
                        <span class="summary-value">${formData.civilite || ''} ${formData.prenom || ''} ${formData.nom || ''}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Email :</span>
                        <span class="summary-value">${formData.email || ''}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Téléphone :</span>
                        <span class="summary-value">${formData.telephone || ''}</span>
                    </div>`;

            if (formData.adresse) {
                html += `
                    <div class="summary-item">
                        <span class="summary-label">Adresse :</span>
                        <span class="summary-value">${formData.adresse}</span>
                    </div>`;
            }

            html += `
                    <div class="summary-item">
                        <span class="summary-label">Ville :</span>
                        <span class="summary-value">${formData.code_postal || ''} ${formData.ville || ''}</span>
                    </div>
                </div>
                
                <div class="summary-section">
                    <h5>Votre demande</h5>
                    <div class="summary-item">
                        <span class="summary-label">Objet :</span>
                        <span class="summary-value">${formData.objet_demande || ''}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Message :</span>
                        <span class="summary-value">${formData.message || ''}</span>
                    </div>`;

            if (ContactFormState.uploadedFile) {
                html += `
                    <div class="summary-item">
                        <span class="summary-label">Pièce jointe :</span>
                        <span class="summary-value">${ContactFormState.uploadedFile.name}</span>
                    </div>`;
            }

            html += `</div>`;
            return html;
        }
    };

})(jQuery);