<script setup >
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast';

const emit = defineEmits(['submit'])

const toast = useToast();

const resolver = ({ values }) => {
  const errors = {};

  if (!values.hash) {
    errors.hash = [{ message: 'Hash is required.' }];
  }

  if (!values.hash.match('^[a-fA-F0-9]{32}$')) {
    errors.hash = [{ message: 'Value is not md5 hash.' }];
  }

  return {
    errors
  };
};

const onFormSubmit = ({ valid }) => {
  if (valid) {
    toast.add({ severity: 'success', summary: 'Form is submitted.', life: 3000 });
  }
}

const crackIt = ref(null);
const initialValues = ref({
  hash: '',
});

const submit = () => {
  emit('submit', crackIt.value);
}
</script>

<template>
  <div class="card flex justify-center">
    <Toast />

    <Form v-slot="$form" :initialValues :resolver :validateOnValueUpdate="false" :validateOnBlur="true" @submit="onFormSubmit" class="flex flex-col gap-4 w-full sm:w-56">
      <div class="flex flex-col gap-1">
        <label for="search-input">Enter hash to crack:</label>
        <InputText
            v-model="crackIt" name="hash" type="text" placeholder="Hash" fluid />
        <Message v-if="$form.hash?.invalid" severity="error" size="small" variant="simple">{{ $form.hash.error.message }}</Message>
      </div>
      <Button
          type="button"
          label="Crack Password"
          icon="pi pi-search"
          :loading="loadingCrack"
          @click="submit"
          class="search-button"
      />
    </Form>
  </div>
</template>

<style scoped>
.search-button {
  align-self: flex-end;
  margin: 10px 0;
}
</style>