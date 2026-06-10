<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { getPaymentStatus } from '../api';

const route = useRoute();
const loading = ref(true);
const status = ref(null);
const error = ref('');

onMounted(async () => {
  const paymentId = route.query.payment_id;
  if (!paymentId) {
    error.value = 'Не найден идентификатор платежа';
    loading.value = false;
    return;
  }

  try {
    status.value = await getPaymentStatus(paymentId);
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="card">
    <h2>Результат оплаты</h2>

    <p v-if="loading">Проверяем статус оплаты...</p>

    <template v-else-if="status?.paid">
      <p>Оплата прошла успешно.</p>
      <div class="price-box">
        <div class="price-row"><span>Сумма</span><strong>{{ status.amount }} ₽</strong></div>
      </div>
      <p v-if="status.application_uuid" style="color:#666;font-size:.9rem">
        Заявка: {{ status.application_uuid }}
      </p>
    </template>

    <template v-else-if="status">
      <p>Статус платежа: <strong>{{ status.status }}</strong></p>
      <p v-if="status.status === 'pending'">Если деньги списались, обновите страницу через минуту.</p>
    </template>

    <p v-if="error" class="error">{{ error }}</p>

    <p style="margin-top:24px">
      <a href="/">← Вернуться к регистрации</a>
    </p>
  </div>
</template>
