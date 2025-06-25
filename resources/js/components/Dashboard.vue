<template>
  <div>
    <h1>Main Dashboard</h1>
    <p>Welcome! Please select your role dashboard.</p>
    <nav>
      <router-link to="/admin">Admin Dashboard</router-link> |
      <router-link to="/collector">Collector Dashboard</router-link> |
      <router-link to="/donor">Donor Dashboard</router-link>
    </nav>
  </div>
</template>

<script>
export default {
  name: 'Dashboard',
};
</script>
