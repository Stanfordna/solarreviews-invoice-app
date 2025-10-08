<script setup>
import { ref } from 'vue';

const postData = ref({}); // set this in event listener watch function

const submitPost = async () => {
  try {
    const response = await fetch('https://jsonplaceholder.typicode.com/posts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(postData.value)
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    console.log('Post created:', data);
    // Optionally, reset the form or update UI
    postData.value = { title: '', body: '' };
  } catch (error) {
    console.error('Error submitting post:', error);
  }
};
</script>

<template>
</template>
