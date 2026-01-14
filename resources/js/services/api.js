import axios from "axios";

export const sendMessageToAI = async (message) => {
  try {
    const response = await axios.post("/api/chat", { message });
    return response.data.reply;
  } catch (error) {
    console.error("Error communicating with AI:", error);
    return "Sorry, something went wrong.";
  }
};
