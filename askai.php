<?php
session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ask AI</title>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.8/purify.min.js"></script> <style>
        /* --- CSS reverted to original --- */
      * {
        box-sizing: border-box;
      }

      body,
      html {
        margin: 0;
        padding: 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
          Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji",
          "Segoe UI Symbol";
        height: 100%;
        background-color: #f0f2f5;
        display: flex;
        justify-content: center;
        align-items: center;
      }

      #chat-container {
        width: 95%;
        max-width: 1000px;
        min-width: 1000px; /* Original min-width */
        height: 90vh;
        max-height: 900px;
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        display: flex;
        flex-direction: column;
        overflow: hidden;
      }

      header {
        background-color: #ffffff;
        color: #333;
        padding: 15px 20px;
        text-align: center;
        border-bottom: 1px solid #e0e0e0;
        flex-shrink: 0;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      header h1 {
        margin: 0;
        font-size: 1.3em;
        font-weight: 600;
        /* Removed flex-grow and centering adjustments from previous edit */
      }

      #clear-chat {
        background-color: #f44336;
        color: white;
        border: none;
        padding: 6px 14px;
        border-radius: 16px;
        cursor: pointer;
        font-size: 0.9em;
        transition: background-color 0.2s ease;
        /* Removed flex-shrink from previous edit */
      }

      #clear-chat:hover {
        background-color: #c62828;
      }

      #chat-box {
        flex-grow: 1;
        overflow-y: auto;
        padding: 20px;
        background-color: #f9f9f9;
        display: flex;
        flex-direction: column;
        gap: 15px;
        min-width: 1000px; /* Original min-width */
      }

      .message {
        max-width: 80%;
        padding: 12px 18px;
        border-radius: 20px;
        line-height: 1.5;
        word-wrap: break-word;
        overflow-wrap: break-word;
        position: relative;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        /* Removed overflow: hidden from previous edit */
      }

      .message p {
        margin: 0; /* Original: no bottom margin */
      }

      .user-message {
        background-color: #007bff;
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 6px;
      }

      .ai-message {
        background-color: #e9e9eb;
        color: #2c2c2c;
        align-self: flex-start;
        border-bottom-left-radius: 6px;
      }

       /* Removed additional styling for Markdown elements like code, pre, ul, etc. */
       /* They will now inherit base styles or browser defaults */

      #input-area {
        display: flex;
        align-items: center;
        padding: 15px 20px;
        border-top: 1px solid #e0e0e0;
        background-color: #ffffff;
        flex-shrink: 0;
      }

      #user-input { /* Styling applies to the textarea now */
        flex-grow: 1;
        padding: 12px 18px;
        border: 1px solid #ccc;
        border-radius: 22px;
        margin-right: 12px;
        font-size: 1em;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        resize: none;
        /* Height will be managed by JS */
        line-height: 1.4; /* Keep line-height */
        overflow-y: hidden; /* Hide scrollbar initially */
      }

      #user-input:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.2);
      }

      #send-button {
        padding: 10px 18px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 22px;
        cursor: pointer;
        font-size: 1em;
        font-weight: 500;
        transition: background-color 0.2s ease;
        flex-shrink: 0;
        /* Removed fixed height from previous edit */
      }

      #send-button:hover {
        background-color: #0056b3;
      }

      /* Original Scrollbar styles */
      #chat-box::-webkit-scrollbar {
        width: 8px;
      }

      #chat-box::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
      }

      #chat-box::-webkit-scrollbar-thumb {
        background: #c5c5c5;
        border-radius: 10px;
      }

      #chat-box::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
      }
        /* --- End of original CSS --- */
    </style>
  </head>
  <body>
    
    <div id="chat-container">
      <header>
         <h1> Ask AI </h1> <button id="clear-chat">Clear Chat</button>
      </header>
      <div id="chat-box">
        </div>
      <div id="input-area">
        <input
          type="text"
          id="user-input"
          placeholder="Type your question here..."
          autocomplete="off"
        />
        <button id="send-button">Send</button>
      </div>
    </div>

    <script>
    
      document.addEventListener("DOMContentLoaded", async () => {
        const chatBox = document.getElementById("chat-box");
        const userInput = document.getElementById("user-input"); // Now an input field again
        const sendButton = document.getElementById("send-button");
        const clearChatBtn = document.getElementById("clear-chat");

        // Load saved messages on page load
        function loadMessages() {
          const savedMessages = JSON.parse(
            localStorage.getItem("chatMessages") || "[]"
          );
          chatBox.innerHTML = '';
          savedMessages.forEach(({ text, sender }) => {
            addMessage(text, sender, false);
          });
          if (chatBox.scrollHeight > chatBox.clientHeight) {
             chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: "auto" });
          }
        }

        // Save message to localStorage
        function saveMessage(text, sender) {
          const messages = JSON.parse(
            localStorage.getItem("chatMessages") || "[]"
          );
          messages.push({ text, sender });
          localStorage.setItem("chatMessages", JSON.stringify(messages));
        }

        // Updated addMessage function
        function addMessage(text, sender, shouldSave = true) {
            const messageDiv = document.createElement("div");
            messageDiv.classList.add("message", `${sender}-message`);

            if (sender === 'ai') {
                // Configure marked to handle line breaks properly
                const markedOptions = {
                    breaks: true, // Convert single line breaks to <br>
                    gfm: true // Enable GitHub Flavored Markdown (includes breaks)
                };
                // 1. Parse Markdown to HTML using Marked.js with options
                const rawHtml = marked.parse(text, markedOptions);
                // 2. Sanitize the generated HTML using DOMPurify
                const cleanHtml = DOMPurify.sanitize(rawHtml);
                // 3. Insert the sanitized HTML into the message div
                //    Wrap in a paragraph to maintain consistency with original user message structure if needed
                 messageDiv.innerHTML = `<p>${cleanHtml}</p>`; // Wrap sanitized HTML in <p>
            } else {
                // For user messages (from input field), create a paragraph and set textContent
                const paragraph = document.createElement("p");
                // paragraph.style.whiteSpace = 'pre-wrap'; // Not needed for single-line input
                paragraph.textContent = text;
                messageDiv.appendChild(paragraph);
            }

            chatBox.appendChild(messageDiv);
            chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: "smooth" });

            if (shouldSave) {
                saveMessage(text, sender);
            }

            return messageDiv; // Return the created div
        }


        async function generateAIResponse(text) {
          const apiKey = "AIzaSyDbGzHhMGvjMiWHgNGB-Hq3YST87mKovaU"; 
          const model = "gemini-1.5-flash-latest";
          const url = `https://generativelanguage.googleapis.com/v1beta/models/${model}:generateContent?key=${apiKey}`;

          const requestData = {
            contents: [ { parts: [ { text: text } ] } ],
             safetySettings: [
               { category: "HARM_CATEGORY_HARASSMENT", threshold: "BLOCK_MEDIUM_AND_ABOVE" },
               { category: "HARM_CATEGORY_HATE_SPEECH", threshold: "BLOCK_MEDIUM_AND_ABOVE" },
               { category: "HARM_CATEGORY_SEXUALLY_EXPLICIT", threshold: "BLOCK_MEDIUM_AND_ABOVE" },
               { category: "HARM_CATEGORY_DANGEROUS_CONTENT", threshold: "BLOCK_MEDIUM_AND_ABOVE" },
             ],
          };
          console.log("Sending request to Gemini:", JSON.stringify(requestData, null, 2));

          try {
              const response = await fetch(url, {
                  method: "POST",
                  headers: { "Content-Type": "application/json" },
                  body: JSON.stringify(requestData),
              });

              const responseData = await response.json();

              if (!response.ok) {
                  console.error("API Error Response:", responseData);
                  const errorMsg = responseData.error?.message || `HTTP Error ${response.status}`;
                  throw new Error(`API Error: ${errorMsg}`);
              }

              console.log("API Success Response:", responseData);

              if (responseData.candidates && responseData.candidates.length > 0) {
                   const candidate = responseData.candidates[0];
                   if (candidate.finishReason && candidate.finishReason !== "STOP" && candidate.finishReason !== "MAX_TOKENS") {
                       console.warn(`Generation finished unexpectedly: ${candidate.finishReason}`);
                       return `My response was cut short due to: ${candidate.finishReason}. Please try rephrasing your request.`;
                   }
                   if (candidate.content && candidate.content.parts && candidate.content.parts.length > 0) {
                       return candidate.content.parts[0].text;
                   } else {
                      console.warn("Received candidate with no content parts.");
                      return `I received an incomplete response. (Reason: ${candidate.finishReason || 'Unknown'})`;
                   }
              } else if (responseData.promptFeedback && responseData.promptFeedback.blockReason) {
                 console.error("Prompt blocked:", responseData.promptFeedback.blockReason);
                 return `I cannot process that request due to safety restrictions (${responseData.promptFeedback.blockReason}).`;
              } else {
                  console.error("Unexpected API response structure:", responseData);
                  return "Sorry, I received an unexpected response format from the AI.";
              }
          } catch (error) {
              console.error("Fetch or Processing Error:", error);
              return `Sorry, I couldn't process that request. Please check your connection or API key. (Error: ${error.message})`;
          }
        }

        async function handleSendMessage() {
          const text = userInput.value.trim();
          if (text === "") return;

          addMessage(text, "user");
          userInput.value = ""; // Clear input after sending

          const loadingMsgDiv = addMessage("Thinking...", "ai", false); // Simpler placeholder

          try {
            const aiResponseMarkdown = await generateAIResponse(text);

            const markedOptions = { breaks: true, gfm: true };
            const rawHtml = marked.parse(aiResponseMarkdown, markedOptions);
            const cleanHtml = DOMPurify.sanitize(rawHtml);

            // Update the placeholder div itself, wrapping in <p> for consistency
            loadingMsgDiv.innerHTML = `<p>${cleanHtml}</p>`;
            loadingMsgDiv.classList.add("message", "ai-message"); // Ensure classes are set

            saveMessage(aiResponseMarkdown, "ai");

          } catch (error) {
              console.error("Error during AI response generation:", error);
               loadingMsgDiv.innerHTML = '<p>Sorry, something went wrong while getting the response.</p>';
          } finally {
             chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: "smooth" });
          }
        }

        // Event Listeners
        sendButton.addEventListener("click", handleSendMessage);

        userInput.addEventListener("keypress", (event) => {
          // Send on Enter (no shift key check needed for input type=text)
          if (event.key === "Enter") {
            event.preventDefault(); // Prevent default form submission if applicable
            handleSendMessage();
          }
        });

        clearChatBtn.addEventListener("click", () => {
           if (confirm("Are you sure you want to clear the chat history?")) {
               localStorage.removeItem("chatMessages");
               chatBox.innerHTML = ""; // Clear the display
           }
        });

        // Initial Setup
        userInput.focus();
        loadMessages();
      });
    </script>
  </body>
</html>