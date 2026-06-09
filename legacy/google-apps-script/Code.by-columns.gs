/**
 * Google Apps Script v2 — поиск колонок по заголовкам (не по индексам).
 * Разверните как новый Web App и укажите URL в GOOGLE_SHEETS_WEBHOOK_URL.
 *
 * Ожидаемые заголовки (регистр не важен):
 * - email, phone, applicationUuid
 * - payment1Amount, payment1Date, payment1Id
 * - payment2Amount, payment2Date, payment2Id
 * - totalAmount, paidTotal, remaining, notes
 */

var SHEET_ID = '1r2LoY04p4pCoknF7s14VkGBnTz-IxHaIkG8Un3W1bA0';
var SHEET_NAME = 'Регистрации';

function doPost(e) {
  try {
    if (!e || !e.postData || !e.postData.contents) {
      return jsonResponse({ status: 'error', message: 'No post data' });
    }

    var data = JSON.parse(e.postData.contents);
    var ss = SpreadsheetApp.openById(SHEET_ID);
    var sheet = ss.getSheetByName(SHEET_NAME);
    if (!sheet) {
      return jsonResponse({ status: 'error', message: 'Sheet not found' });
    }

    var col = buildColumnMap(sheet);
    var lastRow = sheet.getLastRow();
    if (lastRow < 2) {
      return jsonResponse({ status: 'error', message: 'No rows' });
    }

    var email = (data.email || '').toString().trim();
    var phoneNorm = normalizePhoneDigits(data.phone || '');
    var paymentId = (data.paymentId || '').toString().trim();
    var appUuid = (data.applicationUuid || '').toString().trim();

    var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
    var values = sheet.getRange(2, 1, lastRow - 1, sheet.getLastColumn()).getValues();

    var targetRow = findTargetRow(values, col, email, phoneNorm, paymentId, appUuid, parseFloat(data.amount));

    if (!targetRow) {
      return jsonResponse({ status: 'error', message: 'User not found' });
    }

    var rowData = values[targetRow - 2];
    var payment1Id = getCell(rowData, col, 'payment1Id');
    var payment2Id = getCell(rowData, col, 'payment2Id');
    var formattedDate = formatPaidAt(data.paidAt);
    var amountText = data.amount + ' ' + (data.currency || 'RUB');

    if (paymentId === payment1Id) {
      setCell(sheet, targetRow, col, 'payment1Amount', amountText);
      if (formattedDate) setCell(sheet, targetRow, col, 'payment1Date', formattedDate);
    } else if (paymentId === payment2Id) {
      setCell(sheet, targetRow, col, 'payment2Amount', amountText);
      if (formattedDate) setCell(sheet, targetRow, col, 'payment2Date', formattedDate);
    } else if (!payment1Id) {
      setCell(sheet, targetRow, col, 'payment1Amount', amountText);
      if (formattedDate) setCell(sheet, targetRow, col, 'payment1Date', formattedDate);
      setCell(sheet, targetRow, col, 'payment1Id', paymentId);
    } else if (!payment2Id) {
      setCell(sheet, targetRow, col, 'payment2Amount', amountText);
      if (formattedDate) setCell(sheet, targetRow, col, 'payment2Date', formattedDate);
      setCell(sheet, targetRow, col, 'payment2Id', paymentId);
    }

    if (data.paidTotal) setCell(sheet, targetRow, col, 'paidTotal', data.paidTotal + ' RUB');
    if (data.remaining) setCell(sheet, targetRow, col, 'remaining', data.remaining + ' RUB');

    return jsonResponse({ status: 'ok', row: targetRow });
  } catch (err) {
    return jsonResponse({ status: 'error', message: err.message });
  }
}

function buildColumnMap(sheet) {
  var headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  var map = {};
  for (var i = 0; i < headers.length; i++) {
    var key = String(headers[i] || '').trim().toLowerCase();
    if (key) map[key] = i;
  }
  return map;
}

function findTargetRow(values, col, email, phoneNorm, paymentId, appUuid, amountFloat) {
  var candidates = [];

  for (var i = 0; i < values.length; i++) {
    var row = values[i];
    var rowEmail = getCell(row, col, 'email');
    var rowPhone = normalizePhoneDigits(getCell(row, col, 'phone'));
    var rowUuid = getCell(row, col, 'applicationuuid');

    if (paymentId) {
      var p1 = getCell(row, col, 'payment1id');
      var p2 = getCell(row, col, 'payment2id');
      if (p1 === paymentId || p2 === paymentId) return i + 2;
    }

    if (appUuid && rowUuid === appUuid) return i + 2;

    if (email && phoneNorm && rowEmail === email && rowPhone === phoneNorm) {
      candidates.push({ row: i + 2, total: parseMoney(getCell(row, col, 'totalamount')) });
    }
  }

  if (candidates.length === 0) return null;
  if (!isNaN(amountFloat)) {
    for (var j = 0; j < candidates.length; j++) {
      if (candidates[j].total === amountFloat) return candidates[j].row;
    }
  }
  return candidates[candidates.length - 1].row;
}

function getCell(row, col, name) {
  var idx = col[name.toLowerCase()];
  if (idx === undefined) return '';
  return (row[idx] || '').toString().trim();
}

function setCell(sheet, row, col, name, value) {
  var idx = col[name.toLowerCase()];
  if (idx !== undefined) sheet.getRange(row, idx + 1).setValue(value);
}

function parseMoney(s) {
  return parseFloat(String(s).replace(/[^\d.,]/g, '').replace(',', '.')) || 0;
}

function formatPaidAt(paidAt) {
  if (!paidAt) return '';
  var d = new Date(paidAt);
  if (isNaN(d.getTime())) return '';
  return Utilities.formatDate(d, Session.getScriptTimeZone(), 'dd.MM.yyyy HH:mm:ss');
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
