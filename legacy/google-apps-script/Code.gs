/**
 * @deprecated Логика поиска по индексам колонок.
 * Новая версия: legacy/google-apps-script/Code.by-columns.gs
 *
 * Google Apps Script — обновление Google Sheets при оплате (оригинал).
 */

function doPost(e) {
  try {
    if (!e || !e.postData || !e.postData.contents) {
      return jsonResponse({ status: 'error', message: 'No post data' });
    }

    var data = JSON.parse(e.postData.contents);

    var SHEET_ID = '1r2LoY04p4pCoknF7s14VkGBnTz-IxHaIkG8Un3W1bA0';
    var SHEET_NAME = 'Регистрации';

    var ss = SpreadsheetApp.openById(SHEET_ID);
    var sheet = ss.getSheetByName(SHEET_NAME);
    if (!sheet) {
      return jsonResponse({ status: 'error', message: 'Sheet not found' });
    }

    var email = (data.email || '').toString().trim();
    var normalizedPhone = normalizePhoneDigits(data.phone || '');
    var paymentId = (data.paymentId || '').toString().trim();
    var amountFloat = parseFloat(data.amount);

    var lastRow = sheet.getLastRow();
    if (lastRow < 2) {
      return jsonResponse({ status: 'error', message: 'No rows' });
    }

    var lastCol = sheet.getLastColumn();
    var values = sheet.getRange(2, 1, lastRow - 1, lastCol).getValues();

    var candidateRows = [];
    var paymentIdMatchRow = null;

    for (var i = 0; i < values.length; i++) {
      var row = values[i];
      var rowEmail = (row[1] || '').toString().trim();
      var rowPhoneNorm = normalizePhoneDigits(row[2]);
      var rowAmountRaw = row[8];
      var rowPaymentId1 = (row[13] || '').toString().trim();
      var rowPaymentId2 = (row[17] || '').toString().trim();

      if (paymentId && (rowPaymentId1 === paymentId || rowPaymentId2 === paymentId)) {
        paymentIdMatchRow = i + 2;
        break;
      }

      if (email && normalizedPhone && rowEmail === email && rowPhoneNorm === normalizedPhone) {
        candidateRows.push({
          rowNumber: i + 2,
          amountCell: parseFloat(String(rowAmountRaw).replace('₽', '').replace(',', '.')) || 0,
        });
      }
    }

    var targetRow = null;

    if (paymentIdMatchRow) {
      targetRow = paymentIdMatchRow;
    } else if (candidateRows.length > 0) {
      if (!isNaN(amountFloat)) {
        for (var j = 0; j < candidateRows.length; j++) {
          if (candidateRows[j].amountCell === amountFloat) {
            targetRow = candidateRows[j].rowNumber;
            break;
          }
        }
      }
      if (!targetRow) {
        targetRow = candidateRows[candidateRows.length - 1].rowNumber;
      }
    }

    if (!targetRow) {
      return jsonResponse({ status: 'error', message: 'User not found' });
    }

    if (candidateRows.length > 1) {
      var duplicateRow = candidateRows[candidateRows.length - 1].rowNumber;
      if (duplicateRow !== targetRow) {
        sheet.getRange(duplicateRow, 20).setValue('ОПЛАТА ПЕРЕНЕСЕНА В СТРОКУ №' + targetRow);
      }
    }

    var rowData = values[targetRow - 2];
    var totalCost = parseFloat(String(rowData[8]).replace('₽', '').replace(',', '.').replace(/\s/g, '')) || 0;
    var payment1Id = (rowData[13] || '').toString().trim();
    var payment2Id = (rowData[17] || '').toString().trim();

    var formattedDate = '';
    if (data.paidAt) {
      var parsedDate = parseDateString(data.paidAt);
      if (parsedDate) {
        formattedDate = Utilities.formatDate(parsedDate, Session.getScriptTimeZone(), 'dd.MM.yyyy HH:mm:ss');
      }
    }

    var amountText = data.amount + ' ' + (data.currency || 'RUB');

    if (paymentId === payment1Id) {
      sheet.getRange(targetRow, 12).setValue(amountText);
      if (formattedDate) sheet.getRange(targetRow, 13).setValue(formattedDate);
    } else if (paymentId === payment2Id) {
      sheet.getRange(targetRow, 16).setValue(amountText);
      if (formattedDate) sheet.getRange(targetRow, 17).setValue(formattedDate);
    } else if (!payment1Id) {
      sheet.getRange(targetRow, 12).setValue(amountText);
      if (formattedDate) sheet.getRange(targetRow, 13).setValue(formattedDate);
      sheet.getRange(targetRow, 14).setValue(paymentId);
    } else if (!payment2Id) {
      sheet.getRange(targetRow, 16).setValue(amountText);
      if (formattedDate) sheet.getRange(targetRow, 17).setValue(formattedDate);
      sheet.getRange(targetRow, 18).setValue(paymentId);
      sheet.getRange(targetRow, 15).setValue(data.amount);

      var paymentType = (sheet.getRange(targetRow, 11).getValue() || '').toString().trim();
      if (paymentType && paymentType.indexOf('Оплата-второй-половины') === -1) {
        sheet.getRange(targetRow, 11).setValue(paymentType + ', Оплата-второй-половины');
      }
    }

    var payment1 = parseFloat(String(sheet.getRange(targetRow, 12).getValue()).replace(/[^\d.,]/g, '').replace(',', '.')) || 0;
    var payment2 = parseFloat(String(sheet.getRange(targetRow, 16).getValue()).replace(/[^\d.,]/g, '').replace(',', '.')) || 0;
    var paidTotal = payment1 + payment2;
    var remaining = Math.max(0, totalCost - paidTotal);

    sheet.getRange(targetRow, 19).setValue(paidTotal.toFixed(2) + ' RUB');
    sheet.getRange(targetRow, 20).setValue(remaining.toFixed(2) + ' RUB');

    return jsonResponse({ status: 'ok', row: targetRow });
  } catch (err) {
    Logger.log('🔥 Ошибка doPost: ' + err);
    return jsonResponse({ status: 'error', message: err.message });
  }
}

function jsonResponse(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}

function normalizePhoneDigits(s) {
  if (!s) return '';
  var digits = String(s).replace(/\D/g, '');
  if (digits.length === 11 && digits.charAt(0) === '8') digits = '7' + digits.slice(1);
  if (digits.length === 10) digits = '7' + digits;
  return digits;
}

function parseDateString(s) {
  if (!s || typeof s !== 'string') return null;
  var d = new Date(s);
  if (!isNaN(d.getTime())) return d;
  var m = s.match(/^(\d{4})-(\d{2})-(\d{2})[\sT]?(\d{2}):(\d{2}):(\d{2})/);
  if (m) return new Date(Date.UTC(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +m[6]));
  return null;
}
