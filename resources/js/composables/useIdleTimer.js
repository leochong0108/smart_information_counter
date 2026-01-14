import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';

/**
 * @param {number} timeoutSeconds
 * @param {number} countdownSeconds
 */
export function useIdleTimer(timeoutSeconds = 90, countdownSeconds = 10) {
    const router = useRouter();

    const showTimeoutModal = ref(false);
    const countdown = ref(countdownSeconds);
    const isEndChatConfirmation = ref(false);

    let idleTimer = null;
    let countdownInterval = null;

    const startCountdown = () => {
        showTimeoutModal.value = true;
        countdown.value = countdownSeconds;

        countdownInterval = setInterval(() => {
            countdown.value--;
            if (countdown.value <= 0) {
                endSession();
            }
        }, 1000);
    };

    const resetTimer = () => {
        if (isEndChatConfirmation.value) return;

        clearTimers();
        showTimeoutModal.value = false;

        idleTimer = setTimeout(startCountdown, timeoutSeconds * 1000);
    };

    const endSession = () => {
        clearTimers();
        router.push('/');
    };

    const confirmEndChat = () => {
        isEndChatConfirmation.value = true;
        showTimeoutModal.value = true;
        clearTimeout(idleTimer);
    };

    const continueSession = () => {
        showTimeoutModal.value = false;
        isEndChatConfirmation.value = false;
        resetTimer();
    };

    const clearTimers = () => {
        clearTimeout(idleTimer);
        clearInterval(countdownInterval);
    };

    onMounted(() => resetTimer());
    onUnmounted(() => clearTimers());

    return {
        showTimeoutModal,
        countdown,
        isEndChatConfirmation,
        resetTimer,
        endSession,
        confirmEndChat,
        continueSession
    };
}
