import { afterEach, beforeEach } from 'vitest';
import { resetPage, resetRouter, useForm } from './helpers/inertia.js';

// happy-dom belum menjalankan dialog modal seperti browser sungguhan.
HTMLDialogElement.prototype.showModal = function showModal() {
    this.open = true;
};

HTMLDialogElement.prototype.close = function close() {
    this.open = false;
    this.dispatchEvent(new Event('close'));
};

beforeEach(() => {
    resetPage();
    resetRouter();
    useForm.mockClear();
    localStorage.clear();
});

afterEach(() => {
    localStorage.clear();
});
