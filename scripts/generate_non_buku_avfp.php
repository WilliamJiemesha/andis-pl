<?php

$source = '/Users/william/.codex/attachments/05642b48-17ae-406f-96d1-4117e68ef571/pasted-text.txt';
$target = __DIR__.'/../docs/entrylbl-non-buku.avfp';

$content = file_get_contents($source);

if ($content === false) {
    fwrite(STDERR, "Unable to read AVFP source.\n");
    exit(1);
}

$stockRow = <<<'HTML'

<tr>
<td style="height: 25px;color:#ffd75a">Stock</td>
<td style="height: 25px;">
<div class="ui-grid-b">
  <div class="ui-block-a" style="margin-left:2px;width:50%">
   <input name="txtStock" id="txtStock" style="text-transform:uppercase;" maxlength="60" value="" size="20" placeholder="" autocorrect="off" autocomplete="off">
  </div>
  <div class="ui-block-b">
    <a href="javascript:nbSearchStock()" style="margin-top:-9px;background:#ffd75a;border-color:#ffd75a;color:#111;" class="ui-btn ui-icon-search ui-btn-icon-notext ui-corner-all" title="Cari Stock Non Buku"></a>
  </div></div>
</td>
</tr>
HTML;

$bridgeButton = <<<'HTML'
	<input type="checkbox" id="nbBridge" name="nbBridge" data-mini="true" tabindex=-1 onchange="nbBridgeClicked()">
	<label for="nbBridge" title='Kirim ke Non Buku Project'>
	<span class="fa-stack" style='cursor:pointer;'><i class="fa-solid fa-file-lines fa-stack-1x" style='color:white;font-size:17px;'></i><i class="fa-solid fa-arrows-rotate fa-stack-2x" style='color:#ffd75a;font-size:13px;'></i></span>
	</label>
HTML;

$bridgeScript = <<<'JS'

var NON_BUKU_API_BASE = window.NON_BUKU_API_BASE || 'http://127.0.0.1:8000/api/v1/non-buku/items';

function nbAlert(message, type) {
  if (typeof swal === 'function') {
    swal({ title: message, text: '', type: type || 'info', confirmButtonText: 'Ok' });
  } else {
    alert(message);
  }
}

function nbValue(selector, fallbackSelector) {
  var el = $(selector);
  var value = (el.val() || '').trim();

  if (!value && fallbackSelector) {
    value = ($(fallbackSelector).attr('placeholder') || '').trim();
  }

  return value.toUpperCase();
}

function nbSetValue(selector, value) {
  $(selector).val(value || '').trigger('change');
}

function nbQtyAmount() {
  var qty = nbValue('#txtJumlah');
  var cetak = nbValue('#txtQCetak');
  var amount = parseInt((qty || cetak || '1').replace(/[^0-9]/g, ''), 10);

  return (!amount || amount < 1) ? 1 : amount;
}

function nbPayload() {
  return {
    barang: nbValue('#txtBarang', '#txtBarang'),
    tipe: nbValue('#txtTipe', '#txtTipe'),
    merk: nbValue('#txtMerk', '#txtMerk'),
    amount: nbQtyAmount(),
    alias: ($('#txtStock').val() || '').trim(),
    pc: nbValue('#txtBeli'),
    selling_price_code: nbValue('#txtJual'),
    kode_supplier: nbValue('#txtVendor', '#txtVendor') || 'AVFP'
  };
}

function nbValidatePayload(payload) {
  var barangMax = parseInt($('#txtBarang').attr('maxlength') || '10', 10);
  var tipeMax = parseInt($('#txtTipe').attr('maxlength') || '7', 10);
  var merkMax = parseInt($('#txtMerk').attr('maxlength') || '5', 10);

  if (!payload.barang || payload.barang.length > barangMax) {
    nbAlert('Barang wajib diisi maksimal ' + barangMax + ' karakter.', 'error');
    return false;
  }

  if (!payload.tipe || payload.tipe.length > tipeMax) {
    nbAlert('Tipe wajib diisi maksimal ' + tipeMax + ' karakter.', 'error');
    return false;
  }

  if (!payload.merk || payload.merk.length > merkMax) {
    nbAlert('Merk wajib diisi maksimal ' + merkMax + ' karakter.', 'error');
    return false;
  }

  return true;
}

function nbBridgeMark(on) {
  var value = $('#txtJumlah').val() || '';

  if (on && value.indexOf('~') < 0) {
    $('#txtJumlah').val(value + '~');
  }

  if (!on) {
    $('#txtJumlah').val(value.replace(/~/g, ''));
  }
}

function nbSetBridgeChecked(on) {
  $('#nbBridge').prop('checked', !!on);

  try {
    $('#nbBridge').checkboxradio('refresh');
  } catch (e) {}

  nbBridgeMark(!!on);
}

function nbSearchStock() {
  var payload = nbPayload();

  if (!nbValidatePayload(payload)) {
    return;
  }

  nbSetBridgeChecked(true);

  $.ajax({
    url: NON_BUKU_API_BASE + '/search',
    method: 'GET',
    dataType: 'json',
    data: {
      barang: payload.barang,
      tipe: payload.tipe,
      merk: payload.merk
    },
    success: function(json) {
      if (!json || !json.exists || !json.data) {
        return;
      }

      if (json.data.pc || json.data.kode_modal) {
        nbSetValue('#txtBeli', json.data.pc || json.data.kode_modal);
        onhbeli();
      }

      if (json.data.selling_price_code || json.data.harga_jual_code) {
        nbSetValue('#txtJual', json.data.selling_price_code || json.data.harga_jual_code);
      }

      if (json.data.alias || json.data.official_name) {
        nbSetValue('#txtStock', json.data.alias || json.data.official_name);
      }
    },
    error: function(xhr) {
      if (xhr.status !== 404) {
        nbAlert('Cari Stock gagal.', 'error');
      }
    }
  });
}

function nbBridgeClicked() {
  var isOn = $('#nbBridge').is(':checked');
  nbBridgeMark(isOn);

  if (isOn) {
    nbSyncNonBuku();
  }
}

function nbApplySyncResult(json) {
  if (json && json.data) {
    if (json.data.pc || json.data.kode_modal) {
      nbSetValue('#txtBeli', json.data.pc || json.data.kode_modal);
      onhbeli();
    }

    if (json.data.selling_price_code || json.data.harga_jual_code) {
      nbSetValue('#txtJual', json.data.selling_price_code || json.data.harga_jual_code);
    }

    if (json.data.alias || json.data.official_name) {
      nbSetValue('#txtStock', json.data.alias || json.data.official_name);
    }
  }
}

function nbSyncNonBuku() {
  var payload = nbPayload();

  if (!nbValidatePayload(payload)) {
    return;
  }

  $.ajax({
    url: NON_BUKU_API_BASE + '/sync',
    method: 'POST',
    dataType: 'json',
    contentType: 'application/json',
    data: JSON.stringify(payload),
    success: function(json) {
      nbApplySyncResult(json);
      nbAlert('Non Buku Project sudah diperbarui.', 'success');
    },
    error: function(xhr) {
      var message = 'Bridge Non Buku gagal.';

      if (xhr.responseJSON && xhr.responseJSON.message) {
        message = xhr.responseJSON.message;
      }

      nbAlert(message, 'error');
      nbSetBridgeChecked(false);
    }
  });
}

function nbSyncNonBukuBeforeSubmit() {
  var payload = nbPayload();
  var ok = false;
  var message = 'Bridge Non Buku gagal.';

  if (!nbValidatePayload(payload)) {
    return false;
  }

  $.ajax({
    url: NON_BUKU_API_BASE + '/sync',
    method: 'POST',
    dataType: 'json',
    contentType: 'application/json',
    data: JSON.stringify(payload),
    async: false,
    success: function(json) {
      nbApplySyncResult(json);
      ok = true;
    },
    error: function(xhr) {
      if (xhr.responseJSON && xhr.responseJSON.message) {
        message = xhr.responseJSON.message;
      }
    }
  });

  if (!ok) {
    nbAlert(message, 'error');
    return false;
  }

  return true;
}
JS;

$inserted = 0;

$content = str_replace("</fieldset>\n</div>\n</div>\n</td>", $bridgeButton."\n</fieldset>\n</div>\n</div>\n</td>", $content, $count);
$inserted += $count;

$content = str_replace("</td>\n</tr>\n\n\n</tbody>\n</table>", "</td>\n</tr>\n".$stockRow."\n\n</tbody>\n</table>", $content, $count);
$inserted += $count;

$content = str_replace("\nfunction myenota(){", "\n".$bridgeScript."\n\nfunction myenota(){", $content, $count);
$inserted += $count;

$content = str_replace(
    "\t setCookie(\"tglinputlbl\",document.form.entrydate.value,1);",
    "\t if ($('#nbBridge').is(':checked') && !nbSyncNonBukuBeforeSubmit()) { return false; }\n\t setCookie(\"tglinputlbl\",document.form.entrydate.value,1);",
    $content,
    $count,
);
$inserted += $count;

if ($inserted < 4) {
    fwrite(STDERR, "AVFP generation did not find every insertion point. Insertions: {$inserted}\n");
    exit(1);
}

file_put_contents($target, $content);

echo $target.PHP_EOL;
