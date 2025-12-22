import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';

/**
 * @param {number} timeoutSeconds 无操作多久后弹出提示 (秒)
 * @param {number} countdownSeconds 倒计时多久后跳转 (秒)
 */
export function useIdleTimer(timeoutSeconds = 90, countdownSeconds = 10) {
    const router = useRouter();

    const showTimeoutModal = ref(false);
    const countdown = ref(countdownSeconds);
    const isEndChatConfirmation = ref(false); // 是否是用户主动点击 End Chat

    let idleTimer = null;
    let countdownInterval = null;

    // 开始倒计时 (10s)
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

    // 重置计时器 (用户有操作时调用)
    const resetTimer = () => {
        // 如果是用户主动点击结束，或者正在倒计时，不重置（除非显式取消）
        if (isEndChatConfirmation.value) return;

        clearTimers();
        showTimeoutModal.value = false;

        // 重新开始 90s 计时
        idleTimer = setTimeout(startCountdown, timeoutSeconds * 1000);
    };

    // 彻底结束
    const endSession = () => {
        clearTimers();
        router.push('/');
    };

    // 用户主动点击 "End Chat"
    const confirmEndChat = () => {
        isEndChatConfirmation.value = true;
        showTimeoutModal.value = true;
        clearTimeout(idleTimer); // 暂停空闲检测
    };

    // 用户点击 "Cancel" (继续聊天)
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
