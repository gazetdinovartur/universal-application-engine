<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { calculatePrice, createApplication, createPayment, getProduct } from '../api';

const PRODUCT_SLUG = 'hanuman-fest-2027';

const loading = ref(false);
const submitting = ref(false);
const error = ref('');
const product = ref(null);
const pricing = ref(null);

const form = ref({
  name: '',
  email: '',
  phone: '',
  participationOptionCode: '',
  adultsCount: 1,
  childrenCount: 0,
  transferIncluded: false,
  paymentFactor: 0.5,
});

const participationOptions = computed(() => product.value?.participationOptions ?? []);

async function loadProduct() {
  loading.value = true;
  error.value = '';
  try {
    product.value = await getProduct(PRODUCT_SLUG);
    if (participationOptions.value.length && !form.value.participationOptionCode) {
      form.value.participationOptionCode = participationOptions.value[0].code;
    }
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

async function recalculate() {
  if (!form.value.participationOptionCode) return;
  try {
    pricing.value = await calculatePrice({
      productSlug: PRODUCT_SLUG,
      participationOptionCode: form.value.participationOptionCode,
      adultsCount: Number(form.value.adultsCount || 1),
      childrenCount: Number(form.value.childrenCount || 0),
      transferIncluded: !!form.value.transferIncluded,
      paymentFactor: Number(form.value.paymentFactor || 1),
    });
    error.value = '';
  } catch (e) {
    pricing.value = null;
    error.value = e.message;
  }
}

watch(
  () => [
    form.value.participationOptionCode,
    form.value.adultsCount,
    form.value.childrenCount,
    form.value.transferIncluded,
    form.value.paymentFactor,
  ],
  recalculate,
);

onMounted(async () => {
  await loadProduct();
  await recalculate();
});

async function submit() {
  submitting.value = true;
  error.value = '';

  try {
    const application = await createApplication({
      name: form.value.name,
      email: form.value.email,
      phone: form.value.phone,
      productSlug: PRODUCT_SLUG,
      participationOptionCode: form.value.participationOptionCode,
      adultsCount: Number(form.value.adultsCount || 1),
      childrenCount: Number(form.value.childrenCount || 0),
      transferIncluded: !!form.value.transferIncluded,
      paymentFactor: Number(form.value.paymentFactor || 1),
    });

    const payment = await createPayment({
      applicationUuid: application.uuid,
    });

    window.location.href = payment.gateway_url;
  } catch (e) {
    error.value = e.message;
    submitting.value = false;
  }
}
</script>

<template>
  <div class="card">
    <h2>Регистрация</h2>

    <p v-if="product?.activePricingPeriod">
      Период: <strong>{{ product.activePricingPeriod.name }}</strong>
    </p>

    <form @submit.prevent="submit">
      <div class="field">
        <label>Имя</label>
        <input v-model="form.name" required autocomplete="name" />
      </div>

      <div class="field">
        <label>Email</label>
        <input v-model="form.email" type="email" required autocomplete="email" />
      </div>

      <div class="field">
        <label>Телефон</label>
        <input v-model="form.phone" type="tel" required placeholder="+79..." autocomplete="tel" />
      </div>

      <div class="field">
        <label>Вариант участия</label>
        <select v-model="form.participationOptionCode" required>
          <option v-for="opt in participationOptions" :key="opt.code" :value="opt.code">
            {{ opt.name }}<template v-if="opt.price"> — {{ opt.price }} ₽</template>
          </option>
        </select>
      </div>

      <div class="field">
        <label>Количество взрослых участников</label>
        <input v-model.number="form.adultsCount" type="number" min="1" step="1" required />
      </div>

      <div class="field">
        <label>Количество детей до 16 лет</label>
        <input v-model.number="form.childrenCount" type="number" min="0" step="1" />
      </div>

      <div class="field">
        <label style="display:flex;align-items:center;gap:8px;font-weight:500">
          <input v-model="form.transferIncluded" type="checkbox" />
          Трансфер туда-обратно (+600 ₽ на человека)
        </label>
      </div>

      <div class="field">
        <label>Вариант оплаты</label>
        <select v-model.number="form.paymentFactor">
          <option :value="1">Полная оплата</option>
          <option :value="0.5">Предоплата 50%</option>
        </select>
      </div>

      <div v-if="pricing" class="price-box">
        <div class="price-row">
          <span>Стоимость участия</span>
          <strong>{{ pricing.totalAmount }} ₽</strong>
        </div>
        <div class="price-row">
          <span>К оплате сейчас</span>
          <strong>{{ pricing.payNowAmount }} ₽</strong>
        </div>
        <div class="price-row" v-if="pricing.discountAmount > 0">
          <span>Скидка</span>
          <strong>-{{ pricing.discountAmount }} ₽</strong>
        </div>
        <div class="price-row" style="color:#666;font-size:.9rem">
          <span>{{ pricing.pricingPeriodName }}</span>
          <span>{{ pricing.participationOptionName }}</span>
        </div>
      </div>

      <button class="btn" type="submit" :disabled="submitting || loading || !pricing">
        {{ submitting ? 'Создаём оплату...' : 'Зарегистрироваться и оплатить' }}
      </button>

      <p v-if="error" class="error">{{ error }}</p>
    </form>
  </div>

  <div v-if="submitting" class="loader">
    <div class="loader-box">Создаём страницу оплаты...</div>
  </div>
</template>
