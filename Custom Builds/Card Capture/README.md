# Card Capture

Text an older client one link while you're on the phone with them. From that link they:

1. take a picture of the **front** of their insurance card,
2. take a picture of the **back**,
3. sign your **DocuSign** form with their finger.

Everything lands in a Google Drive folder for that client (`Card front.jpg`, `Card back.jpg`, `Signed form.pdf`). The **Calls** tab in your spreadsheet shows who is done. A sidebar tells the caller which screen the client is on and what to say next.

It runs entirely inside your Google account (Google Sheets + Apps Script), so there's no server or website to maintain.

`mockup.html` is the clickable demo. `apps-script/` is the real thing.

---

## What you need

| | Cost (approx.) | Time to get |
|---|---|---|
| A Google account, ideally a shared office account like `forms@yourdomain.com` so it keeps working if someone leaves | free | minutes |
| **Twilio** account and phone number, for sending texts | ~$1–2/month + about 1¢ per text + one-time registration fees | **1–3 weeks** for texting registration |
| **DocuSign** API access on your existing account | depends on your plan: ask your DocuSign rep | days |

**Start the Twilio registration today.** It's the slowest part. Until it's approved, you can still use Card Capture: the sidebar shows the link and a **Copy link** button, and callers text it from their own phone.

---

## Setup (about 1 hour, one time)

### 1. Make the spreadsheet

1. In Google Drive, create a new Google Sheet named **Card Capture**. Use the office account.
2. Click **Extensions → Apps Script**.
3. In the Apps Script editor, create these files and paste in the contents from the `apps-script` folder:
   - **Script** files (click **+ → Script**): `Code`, `Client`, `Links`, `Twilio`, `DocuSign`. Delete the starter code in `Code` first.
   - **HTML** files (click **+ → HTML**): `Client`, `Panel`, `Settings`.
   - Click **Project Settings** (gear icon), tick **Show "appsscript.json" manifest file**, go back to the editor, open `appsscript.json` and replace it with ours.
4. Click **Save**.
5. Go back to the spreadsheet and reload the page. A **Card Capture** menu appears.
6. Click **Card Capture → Set up this spreadsheet**. Google asks for permission the first time: click **Continue** and **Allow**.

### 2. Publish the client page

1. In the Apps Script editor, click **Deploy → New deployment**.
2. Click the gear next to "Select type" and choose **Web app**.
3. Set **Execute as: Me** and **Who has access: Anyone**. This is required because clients don't have Google accounts.
4. Click **Deploy** and copy the **Web app URL** (it ends in `/exec`).

> **After any code change** go to **Deploy → Manage deployments → pencil icon → Version: New version → Deploy**. The web address stays the same.

### 3. Basic settings

**Card Capture → Settings…**

- **Agency name** and **Office phone**.
- **Google Drive folder**: make a folder such as "Card Capture – Clients", open it, and paste its address from the browser bar.
- **Web app address**: paste the URL from step 2.
- Click **Save**.

### 4. Texting (Twilio)

1. Sign up at twilio.com and buy a local phone number.
2. Complete **A2P 10DLC registration** (Twilio walks you through it under *Messaging → Regulatory Compliance*). Choose the "Customer Care" use case. Sample message:
   *"Hi Margaret, this is Sarah from [Agency]. Tap this link to send us a picture of your insurance card and sign your form: [link]"*
3. From the Twilio Console home page, copy the **Account SID** and **Auth Token** into Settings. Put your Twilio number in **Send from**.
4. Enter your own cell under **Send a test text to** and click **Send test**.

### 5. DocuSign

Set this up first in a free **DocuSign developer (sandbox) account** (developers.docusign.com). Test there, then switch to live.

1. In the sandbox, recreate your form as a template, or share your existing template into it. Note the **role name** of the client signer (for example "Client").
2. Go to **Settings → Apps and Keys → Add App and Integration Key**. Name it "Card Capture".
   - Copy the **Integration Key**.
   - Under *Service Integration*, click **Generate RSA** and copy the **private key** (the whole thing, BEGIN to END).
   - Under *Additional settings → Redirect URIs*, add your **Web app URL** from step 2.
   - Save.
3. On the Apps and Keys page, copy your **User ID** (under *My Account Information*).
4. Open your template and copy its **Template ID** from the template details.
5. In **Card Capture → Settings…**, fill in the DocuSign section (Environment: *Testing*) and click **Save**.
6. Click **Approve DocuSign access**, open the link, sign in to DocuSign and click **Accept**.
7. Click **Test DocuSign**. It should say the template and role were found.

**Going live:** DocuSign reviews an integration before it can be used on your real account ("Go-Live"). After about 20 successful test signings in the sandbox, request Go-Live from the Apps and Keys page. Then, in your **live** DocuSign account, generate a new RSA key, update the settings, set Environment to **Live**, use your live Template ID, and click **Approve DocuSign access** again.

If DocuSign isn't set up yet, Card Capture still works: clients do the two photo steps only.

### 6. Give your callers access

- Share the **Card Capture spreadsheet** with each caller as **Editor**.
- Share the **Drive folder** with each caller as **Editor** too (the panel saves the signed PDF there if it notices a finished signature first).
- The first time each caller opens **Card Capture → Open caller panel**, Google asks them to allow it once.

---

## Daily use (callers)

1. Paste or type clients into the **Calls** tab: **Client name** and **Cell phone** are required. Email is optional.
2. **Card Capture → Open caller panel**. Type your first name once.
3. Call the client. Click their row. Click **Text link to [name]**.
4. Follow the **Say this** box. It changes as the client moves through the steps.
5. When it says **Complete**, the Folder column links to the photos and signed form.

- **Client didn't get the text?** Click **Resend the text**.
- **Link expired** (after 48 hours by default)? Click **Start over with a new link**.
- **Client finished signing but it isn't showing yet?** Click **Check DocuSign now**.

---

## Optional: a shorter, friendlier link

Texts show a long `script.google.com/...` address, which some people distrust. To show your own website instead:

1. Open `redirect/index.html`, paste your Web app URL where it says `PASTE_YOUR_WEB_APP_URL_HERE`.
2. Upload it to your website as `/card/index.html` (for example with the SiteGround File Manager).
3. In Settings, set **Short link address** to `https://yourwebsite.com/card/`.

---

## Good to know

- **Privacy:** each link is a random 32-character code that works for one client and expires. Only people you share the spreadsheet and Drive folder with can see client files.
- **Who owns it:** the client page runs as whoever deployed it (step 2). Use a shared office account so it doesn't break when someone leaves.
- **Google notice:** Google may show a small gray bar at the top of the client page saying it was made with Apps Script. That's normal.
- **Volume:** Google's daily limits are far above 400 calls a week.
