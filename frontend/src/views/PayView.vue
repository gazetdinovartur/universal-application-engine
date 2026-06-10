<script setup>
import { onMounted, ref } from 'vue';
import { getPaymentLink, payFromLink } from '../api';

const props = defineProps({ token: { type: String, required: true } });

const loading = ref(true);
const paying = ref(false);
const error = ref('');
const data = ref(null);

onMounted(async () => {
  try {
    data.value = await getPaymentLink(props.token);
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
});

async function pay() {
  paying.value = true;
  error.value = '';
  try {
    const payment = await payFromLink(props.token);
    window.location.href = payment.gateway_url;
  } catch (e) {
    error.value = e.message;
    paying.value = false;
  }
}
</script>

<template>
  <div class="card">
    <h2>Оплата остатка</h2>

    <p v-if="loading">Загрузка...</p>

    <template v-else-if="data">
      <p>Здравствуйте, <strong>{{ data.application.name }}</strong>!</p>

      <div class="price-box">
        <div class="price-row"><span>Всего по заявке</span><strong>{{ data.application.totalAmount }} ₽</strong></div>
        <div class="price-row"><span>Оплачено</span><strong>{{ data.application.paidAmount }} ₽</strong></div>
        <div class="price-row"><span>Осталось</span><strong>{{ data.application.remainingAmount }} ₽</strong></div>
      </div>

      <button
        v-if="data.application.remainingAmount > 0"
        class="btn"
        :disabled="paying"
        @click="pay"
      >
        {{ paying ? 'Переход к оплате...' : `Оплатить ${data.application.remainingAmount} ₽` }}
      </button>

      <p v-else>Заявка полностью оплачена.</p>
    </template>

    <p v-if="error" class="error">{{ error }}</p>
  </div>
</template>
