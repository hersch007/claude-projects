<?php
/**
 * WP-CLI eval-file script — creates/updates Terms, Privacy, and DPA pages.
 * Run with: wp eval-file create_legal_pages.php --path=/path/to/wp
 */

$pages = array(

    array(
        'slug'    => 'terms',
        'title'   => 'Terms of Service',
        'content' => '
<p><strong>Effective Date: July 3, 2026</strong></p>

<hr />

<h2>1. Acceptance of Terms</h2>
<p>By accessing or using the Start Performance platform ("Platform"), you agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, do not use the Platform.</p>
<p>These Terms apply to all users granted access by a subscribing organization ("Client"). Individual users access the Platform on behalf of their organization, and the Client organization is responsible for ensuring users comply with these Terms.</p>

<h2>2. Description of Service</h2>
<p>Start Performance is a business operating platform that may include modules for CRM, sales, operations, knowledge management, ticketing, and related business functions ("Cores"). The specific Cores available to you depend on your organization\'s subscription.</p>

<h2>3. Access and Account Security</h2>
<ul>
<li>Access is granted through a Personal Identification Number (PIN) assigned to you by your organization\'s administrator.</li>
<li>You are responsible for keeping your PIN confidential and for all activity that occurs under your access credentials.</li>
<li>You must notify your administrator immediately if you believe your PIN has been compromised.</li>
<li>Access is personal and non-transferable. You may not share your PIN with any other person.</li>
</ul>

<h2>4. Acceptable Use</h2>
<p>You agree to use the Platform only for lawful business purposes and in accordance with your organization\'s policies. You may not:</p>
<ul>
<li>Use the Platform to store, process, or transmit unlawful, defamatory, or fraudulent content</li>
<li>Attempt to gain unauthorized access to any part of the Platform or its underlying systems</li>
<li>Reverse engineer, decompile, or attempt to extract source code from the Platform</li>
<li>Use automated means (bots, scrapers) to access the Platform without prior written authorization</li>
<li>Upload malicious code, viruses, or any content intended to disrupt or harm the Platform</li>
</ul>

<h2>5. Data and Content</h2>
<p><strong>Your data:</strong> Your organization owns all data and content you enter into the Platform. Start Performance does not claim any ownership rights over your data.</p>
<p><strong>Your responsibility:</strong> You are responsible for the accuracy, quality, and legality of data you input into the Platform. Do not enter personal information you are not authorized to process.</p>
<p><strong>Backups:</strong> While we take reasonable precautions, you are responsible for maintaining your own backups of critical business data.</p>

<h2>6. Privacy</h2>
<p>Your use of the Platform is also governed by our <a href="/privacy/">Privacy Policy</a>, which is incorporated into these Terms by reference.</p>

<h2>7. Intellectual Property</h2>
<p>Start Performance and its licensors retain all intellectual property rights in the Platform, including all software, design, trademarks, and content we provide. These Terms do not grant you any rights to our intellectual property beyond what is necessary to use the Platform as described here.</p>

<h2>8. Availability and Modifications</h2>
<p>We strive to maintain reliable Platform availability but do not guarantee uninterrupted or error-free operation. We reserve the right to modify, suspend, or discontinue any feature or the Platform as a whole at any time, and to update these Terms at any time with notice provided via the Platform or email. Continued use of the Platform after changes take effect constitutes acceptance of the revised Terms.</p>

<h2>9. Disclaimer of Warranties</h2>
<p>THE PLATFORM IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, OR NON-INFRINGEMENT.</p>

<h2>10. Limitation of Liability</h2>
<p>TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, START PERFORMANCE SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES ARISING FROM YOUR USE OF THE PLATFORM. OUR TOTAL CUMULATIVE LIABILITY SHALL NOT EXCEED THE AMOUNT PAID BY YOUR ORGANIZATION FOR THE PLATFORM IN THE THREE (3) MONTHS PRECEDING THE CLAIM.</p>

<h2>11. Governing Law</h2>
<p>These Terms are governed by applicable law. Any disputes shall be resolved through appropriate legal channels.</p>

<h2>12. Contact</h2>
<p>For questions about these Terms, contact us at: <a href="mailto:support@startperformance.com">support@startperformance.com</a></p>

<p><em>Last updated: July 3, 2026</em></p>
',
    ),

    array(
        'slug'    => 'privacy',
        'title'   => 'Privacy Policy',
        'content' => '
<p><strong>Effective Date: July 3, 2026</strong></p>

<hr />

<h2>1. Overview</h2>
<p>This Privacy Policy describes how Start Performance collects, uses, and protects information in connection with the Start Performance platform ("Platform"). The Platform is accessed exclusively by authorized users of subscribing business organizations ("Clients").</p>

<h2>2. Information We Collect</h2>

<h3>Account and Access Information</h3>
<p>When your organization sets up your account, the following information is stored in the Platform:</p>
<ul>
<li>Your name and email address</li>
<li>Your role within your organization</li>
<li>Access credentials (your PIN is stored in encrypted form — we cannot read it)</li>
<li>Date your account was created</li>
</ul>

<h3>Usage Data</h3>
<p>When you use the Platform, we may log login timestamps, session activity, actions taken within the Platform, browser type, operating system, and IP address for diagnostic and security purposes.</p>

<h3>Business Data You Enter</h3>
<p>The Platform stores all data you input on behalf of your organization — including contacts, companies, notes, tasks, estimates, proposals, contracts, and related records. This data belongs to your organization. We process it solely to provide the Platform\'s functionality.</p>

<h2>3. How We Use Information</h2>
<p>We use collected information to:</p>
<ul>
<li>Authenticate your identity and authorize access to the Platform</li>
<li>Deliver and operate the features of the Platform</li>
<li>Send transactional communications (PIN reset emails, system notifications)</li>
<li>Diagnose and fix technical issues</li>
<li>Maintain security and detect unauthorized access</li>
</ul>
<p>We do not sell your personal information. We do not use your business data for advertising.</p>

<h2>4. How We Share Information</h2>
<p>We do not share your personal information with third parties except:</p>
<ul>
<li><strong>Service Providers:</strong> Hosting, email delivery, and infrastructure providers who process data on our behalf under confidentiality obligations</li>
<li><strong>Your Organization:</strong> Your organization\'s administrator has access to all data associated with your account</li>
<li><strong>Legal Requirements:</strong> Where required by law, regulation, or valid legal process</li>
<li><strong>Business Transfers:</strong> In the event of a merger, acquisition, or sale of assets, with appropriate confidentiality protections</li>
</ul>

<h2>5. Data Storage and Security</h2>
<ul>
<li>All Platform data is stored on secured servers</li>
<li>We use industry-standard encryption for data in transit (TLS/HTTPS)</li>
<li>PINs are hashed using a secure one-way algorithm and cannot be recovered by anyone, including us</li>
<li>We implement access controls to limit who can access stored data</li>
</ul>

<h2>6. Data Retention</h2>
<p>We retain your data for as long as your organization\'s subscription is active. Upon termination, data may be retained for up to 90 days to allow for data export, after which it will be deleted from our systems unless legal obligations require longer retention.</p>

<h2>7. Your Rights</h2>
<p>Depending on your location, you may have rights regarding your personal data, including access, correction, deletion, and portability. To exercise these rights, contact your organization\'s administrator or reach out to us at the contact information below.</p>

<h2>8. Cookies and Local Storage</h2>
<p>The Platform uses browser cookies and local storage to maintain your authenticated session and remember navigation preferences. We do not use tracking cookies or third-party advertising cookies.</p>

<h2>9. Children\'s Privacy</h2>
<p>The Platform is intended for business use by adults. We do not knowingly collect personal information from individuals under 18 years of age.</p>

<h2>10. Changes to This Policy</h2>
<p>We may update this Privacy Policy from time to time. We will notify users of material changes via the Platform or by email. Continued use of the Platform after changes take effect constitutes acceptance of the revised policy.</p>

<h2>11. Contact</h2>
<p>For privacy-related questions: <a href="mailto:privacy@startperformance.com">privacy@startperformance.com</a></p>

<p><em>Last updated: July 3, 2026</em></p>
',
    ),

    array(
        'slug'    => 'dpa',
        'title'   => 'Data Processing Agreement',
        'content' => '
<p><strong>Effective Date: July 3, 2026</strong></p>

<hr />

<p>This Data Processing Agreement ("DPA") is entered into between Start Performance ("Processor") and the subscribing business organization ("Controller" or "Client") that has agreed to the Start Performance Terms of Service. This DPA is incorporated into and forms part of the agreement between the parties.</p>

<h2>1. Definitions</h2>
<ul>
<li><strong>"Controller"</strong> means the Client organization that determines the purposes and means of processing personal data through the Platform.</li>
<li><strong>"Processor"</strong> means Start Performance, which processes personal data on behalf of the Controller.</li>
<li><strong>"Personal Data"</strong> means any information relating to an identified or identifiable natural person that is processed through the Platform.</li>
<li><strong>"Processing"</strong> means any operation performed on Personal Data, including storage, retrieval, use, disclosure, or deletion.</li>
<li><strong>"Data Subject"</strong> means an individual whose Personal Data is processed.</li>
<li><strong>"Applicable Data Protection Law"</strong> means all applicable laws and regulations relating to the processing of Personal Data, including where applicable the EU GDPR.</li>
</ul>

<h2>2. Scope and Purpose</h2>
<p>Start Performance processes Personal Data solely as a Processor, acting on the Controller\'s documented instructions, to provide the features of the Platform as described in the Terms of Service. Types of Personal Data processed may include names, email addresses, phone numbers, company information, and any other personal information the Controller enters. Processing continues for the duration of the Controller\'s subscription.</p>

<h2>3. Processor Obligations</h2>
<p>Start Performance agrees to:</p>
<ul>
<li><strong>Instructions:</strong> Process Personal Data only on documented instructions from the Controller, unless required to do so by applicable law.</li>
<li><strong>Confidentiality:</strong> Ensure that persons authorized to process Personal Data are subject to appropriate confidentiality obligations.</li>
<li><strong>Security:</strong> Implement appropriate technical and organizational measures including encryption in transit (TLS/HTTPS), secure credential storage (hashed PINs), access controls, and regular security reviews.</li>
<li><strong>Sub-processors:</strong> Not engage new sub-processors without advance notice to the Controller, and impose equivalent data protection obligations on sub-processors.</li>
<li><strong>Data Subject Rights:</strong> Assist the Controller in fulfilling obligations to respond to Data Subject requests insofar as technically feasible.</li>
<li><strong>Breach Notification:</strong> Notify the Controller within 72 hours of discovering a Personal Data breach.</li>
<li><strong>Deletion or Return:</strong> Upon termination, delete or return all Personal Data as chosen by the Controller.</li>
<li><strong>Audit:</strong> Make available information necessary to demonstrate compliance with this DPA.</li>
</ul>

<h2>4. Controller Obligations</h2>
<p>The Controller represents and warrants that it has a valid legal basis for processing the Personal Data it inputs into the Platform, has provided all required notices and obtained all required consents from Data Subjects, and will use the Platform in compliance with Applicable Data Protection Law.</p>

<h2>5. International Data Transfers</h2>
<p>Where Personal Data is transferred outside the European Economic Area (EEA) or other regions with data transfer restrictions, such transfers shall be made in accordance with Applicable Data Protection Law. Start Performance will, upon request, provide information regarding the legal mechanisms used, which may include Standard Contractual Clauses (SCCs) as approved by the European Commission.</p>

<h2>6. Current Sub-processors</h2>
<table>
<thead><tr><th>Category</th><th>Purpose</th></tr></thead>
<tbody>
<tr><td>Web Hosting / Infrastructure</td><td>Hosting the Platform and storing data</td></tr>
<tr><td>Email Delivery</td><td>Sending transactional emails (PIN resets, notifications)</td></tr>
</tbody>
</table>

<h2>7. Liability</h2>
<p>Each party\'s liability under this DPA is subject to the limitations and exclusions set out in the Terms of Service. Nothing in this DPA limits liability that cannot be excluded under Applicable Data Protection Law.</p>

<h2>8. Contact</h2>
<p>For questions about this DPA: <a href="mailto:privacy@startperformance.com">privacy@startperformance.com</a></p>

<h2>9. Order of Precedence</h2>
<p>In the event of a conflict between this DPA and the Terms of Service with respect to the processing of Personal Data, this DPA shall take precedence.</p>

<p><em>Last updated: July 3, 2026</em></p>
',
    ),

);

foreach ( $pages as $page ) {
    $existing = get_page_by_path( $page['slug'], OBJECT, 'page' );
    $args = array(
        'post_type'    => 'page',
        'post_title'   => $page['title'],
        'post_name'    => $page['slug'],
        'post_content' => trim( $page['content'] ),
        'post_status'  => 'publish',
    );

    if ( $existing ) {
        $args['ID'] = $existing->ID;
        wp_update_post( $args );
        WP_CLI::success( 'Updated: ' . $page['title'] . ' (ID ' . $existing->ID . ') — /' . $page['slug'] . '/' );
    } else {
        $id = wp_insert_post( $args );
        WP_CLI::success( 'Created: ' . $page['title'] . ' (ID ' . $id . ') — /' . $page['slug'] . '/' );
    }
}
