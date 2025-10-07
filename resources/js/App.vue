    <template>
      <div class="message-model">
        <p>{{ message }}</p>
      </div>
    </template>

    <script setup>
    import { ref } from 'vue';
    const message = ref("Hello from Vue.js in Laravel!");
    </script>

    <style scoped>
    .message-model {
      /* Your styles here */
    }
    </style>
    