<section id="doc-senders" class="api-doc-section">

    <h4>Senders</h4>

    <p>
        Lista os Senders aprovados e disponíveis para
        utilização pela conta autenticada.
    </p>

    <div class="api-endpoint">
        <span class="api-method api-method-get">GET</span>
        <span class="api-endpoint-path">/senders</span>
    </div>

    <h6>Response</h6>

    <div class="api-code-wrapper">
<pre class="api-code-block"><code>{
    "status": 1,
    "message": "Senders consultados com sucesso.",
    "data": [
        {
            "id": 1,
            "sender": "INFORDATA",
            "type": "EXCLUSIVE",
            "operator": "MOVITEL",
            "status": "ACTIVE"
        }
    ]
}</code></pre>
    </div>

</section>