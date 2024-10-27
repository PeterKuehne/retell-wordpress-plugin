class VoiceAgent {
    constructor(containerId, config) {
        this.containerId = containerId;
        this.agentId = config.agentId;
        this.apiUrl = config.apiUrl;
        
        // Neue SDK-Referenz
        if (typeof window.RetellClient === 'undefined' || typeof window.RetellClient.RetellWebClient === 'undefined') {
            console.error('RetellWebClient ist nicht geladen');
            return;
        }
        this.retellWebClient = new window.RetellClient.RetellWebClient();
        this.isCalling = false;
        
        this.init();
        this.setupEventListeners();
    }

    init() {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        container.innerHTML = `
            <div class="voice-agent-container">
                <button class="mic-button" aria-label="Voice Chat starten/stoppen">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path>
                        <path d="M19 10v1a7 7 0 0 1-14 0v-1"></path>
                        <line x1="12" y1="19" x2="12" y2="22"></line>
                    </svg>
                </button>
                <span class="text">Klicken Sie hier, um einen Anruf zu starten.</span>
            </div>
        `;

        this.button = container.querySelector('.mic-button');
        this.text = container.querySelector('.text');
        
        this.button.addEventListener('click', () => this.toggleConversation());
    }

    setupEventListeners() {
        this.retellWebClient.on("call_started", () => {
            console.log("call started");
            this.text.textContent = "Anruf läuft... zum Stoppen klicken.";
        });
        
        this.retellWebClient.on("call_ended", () => {
            console.log("call ended");
            this.isCalling = false;
            this.updateUI();
        });
        
        this.retellWebClient.on("agent_start_talking", () => {
            console.log("agent_start_talking");
        });
        
        this.retellWebClient.on("agent_stop_talking", () => {
            console.log("agent_stop_talking");
        });
        
        this.retellWebClient.on("error", (error) => {
            console.error("An error occurred:", error);
            this.retellWebClient.stopCall();
            this.isCalling = false;
            this.text.textContent = "Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.";
            this.updateUI();
        });
    }

    async toggleConversation() {
        if (this.isCalling) {
            this.retellWebClient.stopCall();
            this.isCalling = false;
        } else {
            try {
                const registerCallResponse = await this.registerCall();
                if (registerCallResponse.access_token) {
                    await this.retellWebClient.startCall({
                        accessToken: registerCallResponse.access_token,
                    });
                    this.isCalling = true;
                }
            } catch (error) {
                console.error("Failed to start call:", error);
                this.text.textContent = "Fehler beim Starten des Anrufs. Bitte versuchen Sie es erneut.";
                this.isCalling = false;
            }
        }
        this.updateUI();
    }

    async registerCall() {
        try {
            const response = await fetch(`${this.apiUrl}/create-web-call`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    agent_id: this.agentId,
                }),
            });
    
            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }
    
            return await response.json();
        } catch (err) {
            console.error("Error during call registration:", err);
            throw err;
        }
    }

    updateUI() {
        const micIcon = this.isCalling ? 
            `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="1" y1="1" x2="23" y2="23"></line><path d="M9 9v3a3 3 0 0 0 5.12 2.12M15 9.34V4a3 3 0 0 0-5.94-.6"></path><path d="M17 16.95A7 7 0 0 1 5 12v-2m14 0v2a7 7 0 0 1-.11 1.23"></path><line x1="12" y1="19" x2="12" y2="22"></line></svg>` :
            `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path><path d="M19 10v1a7 7 0 0 1-14 0v-1"></path><line x1="12" y1="19" x2="12" y2="22"></line></svg>`;
        
        this.button.innerHTML = micIcon;
        
        if (this.text.textContent !== "Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut." &&
            this.text.textContent !== "Fehler beim Starten des Anrufs. Bitte versuchen Sie es erneut.") {
            this.text.textContent = this.isCalling ? 
                "Anruf läuft... zum Stoppen klicken." :
                "Klicken Sie hier, um einen Anruf zu starten.";
        }
    }
}