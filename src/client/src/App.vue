<script setup>
import { inject, onMounted, ref } from 'vue';

const api = inject('$axios');

const users = ref();
const loading = ref(true);
const loadingCrack = ref(false);
const crackedHash = ref(false);

onMounted(() => {
  fetchUsers(api).then((data) => {
    users.value = data.data;
    loading.value = false;
  });
});

const fetchUsers = (api) => {
  try {
    return api.get('/users');
  } catch (error) {
    console.error(error)
  }
}

const tryCrack = (hash) => {
  loadingCrack.value = true;
  crack(api, hash).then((data) => {
    crackedHash.value = data.data;
    loadingCrack.value = false;
  });
}

const crack = (api, hash) => {
  try {
    return api.post(`/crack/${hash}`);
  } catch (error) {
    loadingCrack.value = false;
    console.error(error)
  }
};
</script>

<template>
  <div class="app-container">
    <!-- Header Section -->
    <header class="app-header">
      <div class="header-content">
        <h1 class="app-title">Password Cracker</h1>
        <p class="app-description">Search and manage user passwords securely</p>
      </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
      <!-- Search Section -->
      <Card class="search-card">
        <template #header>
          <div class="card-header">
            <i class="pi pi-search header-icon"></i>
            <h2>Crack Passwords</h2>
          </div>
        </template>
        <template #content>
          <CrackForm @submit="tryCrack" />
          <CrackedHash v-if="crackedHash" :cracked-hash="crackedHash"/>
        </template>
      </Card>

      <!-- Users Table Section -->
      <Card class="users-card">
        <template #header>
          <div class="card-header">
            <i class="pi pi-users header-icon"></i>
            <h2>User Database</h2>
          </div>
        </template>
        <template #content>
          <DataTable 
            :value="users" 
            :loading="loading" 
            paginator 
            :rows="10" 
            :rowsPerPageOptions="[5, 10, 20, 50]"
            tableStyle="min-width: 100%"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
            currentPageReportTemplate="{first} to {last} of {totalRecords}"
            class="users-table"
            stripedRows
            showGridlines
          >
            <template #empty> 
              <div class="empty-message">
                <i class="pi pi-info-circle"></i>
                <span>No users found.</span>
              </div>
            </template>
            <template #loading> 
              <div class="loading-message">
                <i class="pi pi-spin pi-spinner"></i>
                <span>Loading user data...</span>
              </div>
            </template>
            <Column field="user_id" sortable header="ID" headerStyle="width: 20%" bodyStyle="font-weight: bold"></Column>
            <Column field="password" header="Password Hash" headerStyle="width: 80%"></Column>
          </DataTable>
        </template>
      </Card>
    </main>

    <footer class="app-footer">
      <p>© {{ new Date().getFullYear() }}</p>
    </footer>
  </div>
</template>

<style scoped>
.app-container {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  min-width: 75rem;
  gap: 2rem;
}

.app-header {
  background: linear-gradient(135deg, #4F46E5, #3730A3);
  color: white;
  padding: 2rem;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-bottom: 1rem;
}

.header-content {
  text-align: center;
}

.app-title {
  font-size: 2.5rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.app-description {
  font-size: 1.2rem;
  opacity: 0.9;
}

.main-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  flex: 1;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem;
}

.header-icon {
  font-size: 1.5rem;
  color: #4F46E5;
}

.search-card, .users-card {
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  border-radius: 0.5rem;
  overflow: hidden;
}

.search-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding: 1rem 0;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.input-group label {
  font-weight: 600;
  color: #333333;
}

.search-input {
  width: 100%;
}

.users-table {
  width: 100%;
}

.empty-message, .loading-message {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 2rem;
  color: #6B7280;
}

.app-footer {
  margin-top: auto;
  text-align: center;
  padding: 1.5rem;
  background-color: #F3F4F6;
  color: #6B7280;
  font-size: 0.9rem;
  border-radius: 0.5rem;
}

/* Responsive adjustments */
@media (min-width: 768px) {
  .search-container {
    flex-direction: row;
    align-items: flex-end;
  }

  .input-group {
    flex: 1;
  }

  .search-button {
    margin-left: 1rem;
  }
}

@media (min-width: 1024px) {
  .main-content {
    flex-direction: row;
  }

  .search-card {
    width: 30%;
  }

  .users-card {
    width: 70%;
  }
}
</style>
