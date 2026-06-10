const API_BASE = import.meta.env.VITE_API_BASE || '/api';

async function request(path, options = {}) {
  const response = await fetch(`${API_BASE}${path}`, {
    headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
    ...options,
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new Error(data.error || 'Request failed');
  }

  return data;
}

export function getProduct(slug) {
  return request(`/products/${slug}`);
}

export function calculatePrice(body) {
  return request('/calculate', { method: 'POST', body: JSON.stringify(body) });
}

export function createApplication(body) {
  return request('/applications', { method: 'POST', body: JSON.stringify(body) });
}

export function createPayment(body) {
  return request('/payments', { method: 'POST', body: JSON.stringify(body) });
}

export function getPaymentLink(token) {
  return request(`/payment-links/${token}`);
}

export function payFromLink(token) {
  return request(`/payment-links/${token}/pay`, { method: 'POST' });
}

export function getPaymentStatus(paymentId) {
  return request(`/payments/${paymentId}/status`);
}
