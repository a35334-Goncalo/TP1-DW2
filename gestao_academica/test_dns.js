const dns = require('dns').promises;

async function testDNS() {
    try {
        console.log('Testando resolução SRV...');
        const addresses = await dns.resolveSrv('_mongodb._tcp.cluster0.dgvifkk.mongodb.net');
        console.log('Sucesso:', addresses);
    } catch (err) {
        console.error('Falha no SRV:', err);
        try {
            console.log('Testando resolução A (cluster0.dgvifkk.mongodb.net)...');
            const addresses = await dns.resolve4('cluster0.dgvifkk.mongodb.net');
            console.log('Sucesso A:', addresses);
        } catch (err2) {
            console.error('Falha no A:', err2);
        }
    }
}

testDNS();
