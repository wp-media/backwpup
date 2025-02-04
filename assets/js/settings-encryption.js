(function (_, $, tbRemover, ajaxurl, settingsEncryptionVariables) {
  function makeConstant(value) {
    return {
      value: value,
      writable: false,
      configurable: false,
      enumerable: false,
    };
  }

  function nonce() {
    return document.querySelector("#backwpupajaxnonce").value;
  }

  function waitingMessageVisibilityTo(display) {
    var waiting = document.querySelector("#asymmetric_key_generation_waiting");
    if (waiting) {
      waiting.style.display = display;
    }
  }

  function removeNotice() {
    var noticeEl = document.querySelector("#bwu_encrypt_notice");
    noticeEl && noticeEl.remove();
  }

  function responseNotice(message, type, parent) {
    removeNotice();

    if (!parent) {
      return;
    }

    parent.insertAdjacentHTML(
      "beforebegin",
      '<div id="bwu_encrypt_notice" class="notice notice-' +
        type +
        '"><p>' +
        message +
        "</p></div>"
    );
  }

  var SettingEncryption = {
    generateSymmetricKey: function (evt) {
      var onDone;
      var onFail;
      var data;

      this.disableSaveSettings();

      evt.preventDefault();
      evt.stopPropagation();

      data = {
        action: "encrypt_key_handler",
        task: "generateSymmetricKey",
        _ajax_nonce: nonce(),
      };

      onDone = function (response) {
        var data = response.data;

        if (!response.success) {
          responseNotice(
            data.message,
            "error",
            document.querySelector(".nav-tab-wrapper")
          );
          return;
        }

        $(this.symmetricKeyGenerator).hide();
        $(this.symmetricKeyDownloader).show();

        this.symmetricKeyField.value = data.key;
        this.symmetricKey.innerText = data.key;
        this.symmetricKeyDownloader.setAttribute(
          "href",
          "data:application/octet-stream;charset=utf-16le;base64," +
            btoa(data.key)
        );

        responseNotice(
          data.message,
          "success",
          document.querySelector(".nav-tab-wrapper")
        );
      }.bind(this);

      onFail = function (jqhxr, status, error) {
        responseNotice(
          error,
          "error",
          document.querySelector(".nav-tab-wrapper")
        );
      }.bind(this);

      $.post(ajaxurl, data, onDone).fail(onFail);
    },

    generateAsymmetricKey: function (evt) {
      var data;
      var onDone;
      var onFail;

      this.disableSaveSettings();

      evt.preventDefault();

      waitingMessageVisibilityTo("block");

      data = {
        action: "encrypt_key_handler",
        task: "generateAsymmetricKeyPair",
        _ajax_nonce: nonce(),
      };
      onDone = function (response) {
        var publicKeyDownloader;
        var privateKeyDownloader;

        var data = response.data;
        var publicKey = data.keys.publicKey;
        var privateKey = data.keys.privateKey;

        if (!this.generatedKeyContainer) {
          return;
        }

        if (!response.success) {
          responseNotice(data.message, "error", this.asymmetricModal);
          return;
        }

        publicKeyDownloader = this.generatedKeyContainer.querySelector(
          "#asymmetric_generated_public_key_downloader"
        );
        privateKeyDownloader = this.generatedKeyContainer.querySelector(
          "#asymmetric_generated_private_key_downloader"
        );

        if (!publicKeyDownloader || !privateKeyDownloader) {
          return;
        }

        this.generatedKeyContainer.querySelector(
          ".bwu-generated-key__public .bwu-the-key"
        ).innerText = publicKey;
        this.generatedKeyContainer.querySelector(
          ".bwu-generated-key__private .bwu-the-key"
        ).innerText = privateKey;

        publicKeyDownloader.setAttribute(
          "href",
          "data:text/plain;base64," + btoa(publicKey)
        );
        privateKeyDownloader.setAttribute(
          "href",
          "data:text/plain;base64," + btoa(privateKey)
        );

        waitingMessageVisibilityTo("none");
        this.asymmetricModal.style.display = "block";

        responseNotice(data.message, "success", this.asymmetricModal);
      }.bind(this);

      onFail = function (jqhxr, status, error) {
        responseNotice(error, "error", this.asymmetricModal);
      }.bind(this);

      $.post(ajaxurl, data, onDone).fail(onFail);
    },

    asymmetricPublicKeyValueToField: function (evt) {
      var publicKey = this.generatedKeyContainer.querySelector(
        ".bwu-generated-key__public .bwu-the-key"
      ).innerText;

      evt.preventDefault();
      evt.stopImmediatePropagation();

      if (!this.keyHasBeenDownloaded) {
        alert(settingsEncryptionVariables.mustDownloadPrivateKey);
        return;
      }

      this.asymmetricPublicKeyField.setAttribute("value", publicKey);
      this.asymmetricPublicKey.innerHTML = publicKey;

      tbRemover();

      this.enableSaveSettings();
    },

    validateAsymmetricKeysModal: function (evt) {
      evt.preventDefault();

      removeNotice();

      if (!this.asymmetricPublicKeyField.value) {
        responseNotice(
          settingsEncryptionVariables.publicKeyMissed,
          "warning",
          this.asymmetricValidateModal
        );
        return;
      }
    },

    validateAsymmetricKey: function () {
      var data;
      var onDone;
      var onFail;

      if (!this.asymmetricPrivateKeyField.value) {
        alert(settingsEncryptionVariables.privateKeyMissed);
        return false;
      }

      data = {
        action: "encrypt_key_handler",
        task: "validateAsymmetricKeyPair",
        publickey: this.asymmetricPublicKeyField.value,
        privatekey: this.asymmetricPrivateKeyField.value,
        _ajax_nonce: nonce(),
      };

      onDone = function (response) {
        var data;

        data = response.data;

        if (!response.success) {
          responseNotice(data.message, "error", this.asymmetricValidateModal);
        }

        if (!data.valid) {
          responseNotice(
            settingsEncryptionVariables.invalidPublicKey,
            "error",
            this.asymmetricValidateModal
          );
          return;
        }

        responseNotice(
          settingsEncryptionVariables.validPublicKey,
          "success",
          this.asymmetricValidateModal
        );
      }.bind(this);

      onFail = function (xhr, status, error) {
        responseNotice(error, "error", this.asymmetricValidateModal);
      };

      $.post(ajaxurl, data, onDone).fail(onFail);
    },

    cleanOnThickBoxClosing: function () {
      this.asymmetricModal.style.display = "none";
      this.asymmetricPrivateKeyField.value = "";
    },

    toggleEncryptionType: function (evt) {
      var type = evt.target.dataset.encryptionType;
      $(this.tab.querySelector("#encryption")).val(type);
      var checked = evt.target.checked;
      var typeToShow = null;
      var typeToHide = null;

      if (!checked) {
        $(evt.target).prop("checked", true);
        return;
      } else {
        typeToShow = type;
      }

      switch (typeToShow) {
        case this.TYPE_SYMMETRIC:
          typeToHide = "asymmetric";
          break;
        case this.TYPE_ASYMMETRIC:
          typeToHide = "symmetric";
          break;
      }
      $(this.tab.querySelector("#bwu_encryption_" + typeToHide)).prop('checked', false);
      $(this.tab.querySelector("#" + typeToShow + "_key_container")).show();
      $(this.tab.querySelector("#" + typeToHide + "_key_container")).hide();
    },

    currentOption: function currentOption() {
      return _.filter(this.encryptionKeyOptions, function (item) {
        return item.checked;
      })[0];
    },

    ensureDownloadedKeys: function (evt) {
      var message;

      if (!$(this.tab).is(":visible")) {
        return;
      }

      if (!this.keyHasBeenDownloaded) {
        evt.preventDefault();

        switch (this.currentOption().value) {
          case this.TYPE_SYMMETRIC:
            message = settingsEncryptionVariables.mustDownloadSymmetricKey;
            break;
          case this.TYPE_ASYMMETRIC:
            message = settingsEncryptionVariables.mustDownloadPrivateKey;
            break;
        }

        responseNotice(
          message,
          "error",
          document.querySelector(".nav-tab-wrapper")
        );
        return;
      }

      this.disableSaveSettings();
    },

    enableSaveSettings: function () {
      this.keyHasBeenDownloaded = true;
      $('#encryption_submit').prop('disabled', false);
      $('input[name="encryption_activated"]').prop('disabled', false);
    },

    disableSaveSettings: function () {
      this.keyHasBeenDownloaded = false;
      $('#encryption_submit').prop('disabled', true);
      $('input[name="encryption_activated"]').prop('disabled', true);
    },

    construct: function () {
      var tab;
      var encryptionKeyOptions;

      _.bindAll(
        this,
        "toggleEncryptionType",
        "addListeners",
        "generateSymmetricKey",
        "generateAsymmetricKey",
        "asymmetricPublicKeyValueToField",
        "validateAsymmetricKeysModal",
        "cleanOnThickBoxClosing",
        "validateAsymmetricKey",
        "ensureDownloadedKeys",
        "enableSaveSettings",
        "disableSaveSettings",
        "init"
      );

      tab = document.querySelector("#sidebar-settings-encryption");
      if (!tab) {
        return false;
      }

      encryptionKeyOptions = tab.querySelectorAll(".js-backwpup-bwu-encryption-input");
      if (!encryptionKeyOptions.length) {
        return false;
      }

      this.form = document.querySelector("#encryptionsettingsform");

      this.tab = tab;
      this.encryptionKeyOptions = encryptionKeyOptions;
      this.symmetricKey = this.tab.querySelector("#symmetric_key_code");
      this.symmetricKeyField = this.tab.querySelector("#symmetric_key");
      this.symmetricKeyDownloader = this.tab.querySelector(
        "#symmetric_key_downloader"
      );
      this.asymmetricPublicKey = this.tab.querySelector(
        "#asymmetric_public_key_code"
      );
      this.asymmetricPublicKeyField = this.tab.querySelector(
        "#asymmetric_public_key"
      );
      this.symmetricKeyGenerator = this.tab.querySelector(
        "#symmetric_key_generator"
      );
      this.asymmetricKeyGenerator = this.tab.querySelector(
        "#asymmetric_key_pair_generator"
      );
      this.asymmetricKeyOpenValidateModal = this.tab.querySelector(
        "#asymmetric_key_open_validate_modal"
      );

      this.asymmetricModal = document.querySelector(
        "#asymmetric_generated_key_modal"
      );
      this.generatedKeyContainer =
        this.asymmetricModal.querySelector(".bwu-generated-key");
      this.privateKeyDownloader = this.asymmetricModal.querySelector(
        "#asymmetric_generated_private_key_downloader"
      );
      this.asymmetricKeySelector = this.asymmetricModal.querySelector(
        "#asymmetric_keys_selector"
      );
      this.keyHasBeenDownloaded = true;

      this.asymmetricValidateModal = document.querySelector(
        "#asymmetric_key_pair_validate"
      );
      this.asymmetricKeyDoValidation =
        this.asymmetricValidateModal.querySelector(
          "#asymmetric_key_pair_do_validation"
        );
      this.asymmetricPrivateKeyField =
        this.asymmetricValidateModal.querySelector(
          "#private_key_validate_area"
        );

      return this;
    },

    addListeners: function () {
      _.each(
        this.encryptionKeyOptions,
        function (el) {
          el.addEventListener("change", this.toggleEncryptionType);
        }.bind(this)
      );

      this.symmetricKeyGenerator.addEventListener(
        "click",
        this.generateSymmetricKey
      );
      this.asymmetricKeyGenerator.addEventListener(
        "click",
        this.generateAsymmetricKey
      );

      this.symmetricKeyDownloader.addEventListener(
        "click",
        this.enableSaveSettings
      );
      this.privateKeyDownloader.addEventListener(
        "click",
        this.enableSaveSettings
      );
      this.asymmetricKeySelector.addEventListener(
        "click",
        this.asymmetricPublicKeyValueToField
      );
      this.asymmetricKeyOpenValidateModal.addEventListener(
        "click",
        this.validateAsymmetricKeysModal
      );
      this.asymmetricKeyDoValidation.addEventListener(
        "click",
        this.validateAsymmetricKey
      );
      
      $(this.form).on("submit", function(event) {
        this.ensureDownloadedKeys(event);
      }.bind(this));

      $("body").on("thickbox:removed", this.cleanOnThickBoxClosing);
    },

    init: function () {
      this.toggleEncryptionType({
        target: this.currentOption(),
      });

      this.addListeners();
    },
  };

  window.addEventListener("load", function () {
    var settingEncryption = Object.create(SettingEncryption, {
      TYPE_ASYMMETRIC: makeConstant("asymmetric"),
      TYPE_SYMMETRIC: makeConstant("symmetric"),
    });
    settingEncryption.construct() && settingEncryption.init();
  });
})(
  window._,
  window.jQuery,
  window.tb_remove,
  window.ajaxurl,
  window.settingsEncryptionVariables
);
