<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { useVModel } from '@vueuse/core'

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes['class']
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | number): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <input
    v-model="modelValue"
    data-slot="input"
    :class="cn(
      // Base styles
      'flex h-10 w-full min-w-0 rounded-lg border bg-white px-4 py-2 text-base transition-all duration-200 outline-none',
      
      // Text & colors
      'text-rose-700 placeholder-rose-400/50',
      
      // Border - jelas tapi soft
      'border-rose-200 focus:border-rose-400',
      
      // Focus - sangat subtle
      'focus:ring-1 focus:ring-rose-400/40',
      
      // Shadow - minimal untuk separation
      'shadow-xs hover:shadow-sm',
      
      // States
      'disabled:opacity-50 disabled:bg-rose-50',
      'aria-invalid:border-red-300 aria-invalid:bg-red-50/40',
      
      // File input
      'file:text-rose-700 file:bg-transparent file:border-0',
      
      props.class,
    )"
  >
</template>