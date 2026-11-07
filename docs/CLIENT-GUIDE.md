# Client guide — 2026-10-23

Login karke **WooCommerce → Store dashboard** kholein. Roz ke kaam ke shortcuts yahan milenge. Store rules aur content controls **WooCommerce → Veloria** mein hain.

| Kaam | Kahan badlein |
|---|---|
| Price, stock, photos, size/color | Products → product → Product data → Update |
| Naya product | Products → Add new; variable product ke attributes/variations bhi bharein |
| Header/footer ke menu links | Appearance → Menus → Manage Locations: Header menu, Footer: Collections, Footer: Important links |
| Home ke slider photos, feature/policy strips, Hot Seller, Most loved aur Spotlight | WooCommerce → Veloria → Homepage → Save settings |
| Announcement bar, offer labels, product offers aur trust badges | WooCommerce → Veloria → Offer copy |
| Footer tagline aur Instagram/Facebook links | WooCommerce → Veloria → Brand |
| Support email/phone | WooCommerce → Veloria → Support |
| Seller details aur grievance contact | WooCommerce → Veloria → Legal; sirf business ke sahi details bharein |
| Policy text | Pages → policy page → Edit → Update; page editor use karein |
| Orders/status | WooCommerce → Orders → order |
| Offers, COD fee aur shipping override | WooCommerce → Veloria → Pricing |
| Payment methods | WooCommerce → Settings → Payments |
| Shipping zones/rates | WooCommerce → Settings → Shipping; zones use karne se pehle Veloria → Pricing ka Use "Standard Shipping" only override developer se check karayein |

**Page design:** current Home, About aur Contact layouts Veloria ke PHP theme mein hain. Home ke upar listed controls settings se badalte hain; hero headline, About ki story/photos aur section layout ke liye developer change chahiye. Contact ka support/seller data upar diye settings se aata hai. Pages editor mein in pages ka text badalna poora visible design edit nahi karta.

**Elementor:** free version hi approved hai. Current header/footer, product aur catalog design ke liye Theme Builder editing ka promise nahi hai; un layouts ke changes developer karega. Home/About/Contact ko Elementor mein save karne se current theme layout replace ho sakta hai. Aisa change pehle alag staging par review karayein. Genuine Pro licence ke bina Pro features install/use na karein.

**Easy refund (1.3.1):** WooCommerce → Orders → order → **Veloria — easy full refund**. Paid, already-refunded aur full amount dikhega; amount/quantity/GST mat bharein. Reason aur confirmation ke baad button use karein. Pending result par **check** same provider reference verify karta hai. Native Razorpay line-item/manual refund buttons ab is safe flow par direct karte hain; partial refunds need separate staff reconciliation. Return stock inspect karke separately restock karein. Agar amount badla ya existing partial refund mila, simple flow aage nahi badhega.

**Return/refund:** WooCommerce → Orders → order → **Veloria delivery and returns** mein verified delivery date aur evidence bharein; Completed status akela delivery proof nahi hai. Customer verified delivery se 7 din ke andar return request kar sakta hai. Item inspect karke decision save karein. Refund ke liye **Veloria — easy full refund** panel use karein: reason bharein, full amount confirmation tick karein, phir source refund button dabayein. Amount paid after discounts/GST khud calculate hota hai. Approval email refund payment ki receipt nahi hai. COD mein customer ko paisa lautana confirm karne ke baad manual refund record karein.

**Cancellation:** Order placement se 48 ghante tak eligible, undispatched order ke liye customer **Cancel order** use karega. Courier check/clearance ke baad online refund original payment method par initiate hota hai; courier mapping verify na ho to staff help chahiye. 48 ghante bank settlement ki guarantee nahi hain. Unpaid COD cancel hone par refund nahi hota.

**48 ghante ke baad:** Customer **Request cancellation review** bhejega; order hold par rahega, refund nahi chalega. Order panel mein dispatch/courier verify karke reason ke saath **Approve cancellation and start refund** ya **Decline cancellation request** choose karein. Dispatched parcel recall ka promise na karein. Delivered order ke liye 7-day verified-delivery return aur inspection process hi use karein.

**Cancellation pending / refund retry:** Order ka **Veloria — easy full refund** panel dekhein. Delivery/inspection aur legacy recovery **Delivery, return inspection & advanced recovery** disclosure mein hain. Official Shiprocket channel ka remote order verify na ho to order hold par rahega. Shiprocket mein matching order/parcel cancel aur dispatch stop verify karne ke baad hi courier checkbox aur reference bharein, phir **Retry refund now** choose karein. Razorpay refund balance pehle available hona chahiye. **Confirm earlier refund was rejected** sirf purane unkeyed attempt ke liye hai; timeout/pending refund ko naya refund samajh kar repeat na karein. Return approval aur refund settlement alag states hain.

**Policy publish:** Naya source text `content/policies/cancellation-refund.html` aur `refund-returns.html` mein hai. Release ZIP install karne se existing WordPress Pages update nahi hote. Backup aur owner-approved production change ke saath in dono policy Pages ka matching text update karein; live par page seeder na chalayein.

**Store rules:** 1 item par 10%, 2+ items par 15%, prepaid par extra 5%; combined cap 35%, COD fee ₹149 aur shipping ₹0. Coupons paused hain. Pricing, return days ya coupon mode badalne se pehle owner approval aur staging check karayein; offer/policy text bhi matching rakhein.

**Updates/plugins:** purani staging hata di gayi hai. Pehle separate subdomain aur separate database par staging dobara banwayein; `api.veloriavault.com` live store hai, testing ke liye use na karein. Staging par real payments, courier booking aur outbound email band rakhein. Backup ke baad updates test karein: variations, COD/online checkout, OTP/order email, tracking aur refunds. Production update approval ke baad hi karein.

**Cart/Checkout:** current classic forms rakhein; Cart/Checkout Blocks supported nahi hain. Veloria Commerce deactivate karne se custom offers aur order/return tools band ho jayenge. Content edit ke baad stale page dikhe to LiteSpeed Cache → Toolbox → Purge All karein.
