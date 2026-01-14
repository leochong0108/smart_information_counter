import { ref, onUnmounted } from 'vue';

export function useSpeech() {
    const isListening = ref(false);
    const transcript = ref('');
    let recognition = null;

    const setupSpeech = () => {

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            alert("Your browser does not support voice input.");
            return false;
        }

        recognition = new SpeechRecognition();
        recognition.lang = 'en-US';
        recognition.continuous = false;
        recognition.interimResults = false;

        recognition.onstart = () => { isListening.value = true; };

        recognition.onend = () => { isListening.value = false; };

        recognition.onresult = (event) => {
            const text = event.results[0][0].transcript;
            transcript.value = text;
        };

        recognition.onerror = (event) => {
            isListening.value = false;
            console.error("Speech Error:", event.error);
            if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                alert("Microphone blocked. Please check your browser permission or use HTTPS.");
            }
        };

        return true;
    };

    const toggleSpeech = () => {
        if (!recognition) {
            if (!setupSpeech()) return;
        }

        if (isListening.value) {
            recognition.stop();
        } else {
            transcript.value = '';
            recognition.start();
        }
    };

    onUnmounted(() => {
        if (recognition) recognition.abort();
    });

    return {
        isListening,
        transcript,
        toggleSpeech
    };
}
