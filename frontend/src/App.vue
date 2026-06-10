<template>
  <div class="app">
    <header class="header">
      <h1>Hanuman Fest</h1>
      <p>Регистрация и оплата</p>
    </header>
    <main>
      <RouterView />
    </main>
  </div>
</template>

<style>
* { box-sizing: border-box; }
body {
  margin: 0;
  font-family: system-ui, -apple-system, sans-serif;
  background: #f6f4ef;
  color: #1a1a1a;
}
.app { max-width: 720px; margin: 0 auto; padding: 24px 16px 48px; }
.header h1 { margin: 0 0 4px; font-size: 1.75rem; }
.header p { margin: 0 0 24px; color: #666; }
.card {
  background: #fff;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
}
.field { margin-bottom: 16px; }
.field label { display: block; margin-bottom: 6px; font-weight: 600; font-size: .9rem; }
.field input, .field select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 1rem;
}
.btn {
  display: inline-block;
  padding: 12px 20px;
  border: none;
  border-radius: 8px;
  background: #c45c26;
  color: #fff;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
}
.btn:disabled { opacity: .6; cursor: not-allowed; }
.btn-secondary { background: #555; }
.price-box {
  background: #faf6f0;
  border: 1px solid #eadfce;
  border-radius: 8px;
  padding: 16px;
  margin: 16px 0;
}
.price-row { display: flex; justify-content: space-between; margin: 6px 0; }
.error { color: #b00020; margin-top: 12px; }
.loader {
  position: fixed; inset: 0; background: rgba(0,0,0,.35);
  display: flex; align-items: center; justify-content: center; z-index: 100;
}
.loader-box {
  background: #fff; padding: 24px 32px; border-radius: 12px; text-align: center;
}
</style>
