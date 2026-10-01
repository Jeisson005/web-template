import { NextResponse } from 'next/server';

export async function OPTIONS() {
  return new NextResponse(null, {
    status: 200,
    headers: {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'POST, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type',
    },
  });
}

export async function POST(req: Request) {
  try {
    const body = await req.json();
    const { recaptchaToken, ...formData } = body;

    if (!recaptchaToken) {
      return NextResponse.json({ error: 'reCAPTCHA token is required' }, { status: 400 });
    }

    // 1. Verificar reCAPTCHA con Google
    const recaptchaSecret =
      process.env.RECAPTCHA_SECRET_KEY || '6LcUPforAAAAAGh5FlL1FWf8ATnc8t5g90molOnz';

    const verifyParams = new URLSearchParams({
      secret: recaptchaSecret,
      response: recaptchaToken,
    });

    const verifyRes = await fetch('https://www.google.com/recaptcha/api/siteverify', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: verifyParams.toString(),
    });

    const verifyData = await verifyRes.json();

    if (!verifyData.success) {
      return NextResponse.json(
        { error: 'reCAPTCHA verification failed', details: verifyData['error-codes'] },
        { status: 400 }
      );
    }

    // 2. Preparar datos para Zoho CRM (smartmaps)
    const zohoParams = new URLSearchParams({
      xnQsjsdp: '5e1bc7ca8ff29b70e99dfe39a3191e2c32fc63b7a6770ecc115c3534725ba304',
      xmIwtLD:
        'b7f63ea911aba54798fb9e0c91813db48ccbc8973c654850e0620eb9fa2b2c9cb5c8dc20c0d46a922b4b63e2b42aca77',
      actionType: 'TGVhZHM=',
      returnURL: 'null',
      zc_gad: formData.zc_gad || '',
      aG9uZXlwb3Q: '',

      'First Name': formData.First_Name || formData.firstName || '',
      'Last Name': formData.Last_Name || formData.lastName || '',
      Email: formData.Email || formData.email || '',
      Company: formData.Company || formData.company || '',
      Mobile: formData.Mobile || formData.phone || '',
      LEADCF2: formData.LEADCF2 || formData.country || '-None-',
      Description: formData.Description || formData.details || formData.message || '',

      LEADCF14: formData.LEADCF14 || 'SmartMaps Pro',
      'Lead Status': formData.Lead_Status || 'NO CONTACTADO',
      'Lead Source': formData.Lead_Source || 'Pag, WEB smartmaps',
      service: 'smarturl',
    });

    // 3. Enviar a Zoho CRM
    const zohoRes = await fetch('https://crm.zoho.com/crm/WebToLeadForm', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'User-Agent': 'SmartMaps-WebToLead/1.0',
      },
      body: zohoParams.toString(),
    });

    if (zohoRes.status >= 200 && zohoRes.status < 400) {
      return NextResponse.json(
        { success: true, message: 'Lead created successfully in Zoho CRM' },
        { status: 200 }
      );
    }

    return NextResponse.json(
      { error: 'Zoho request failed', status: zohoRes.status },
      { status: zohoRes.status }
    );
  } catch (err: any) {
    console.error('Error in /api/contact:', err);
    return NextResponse.json({ error: 'Internal server error' }, { status: 500 });
  }
}
