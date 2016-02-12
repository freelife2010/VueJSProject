<?php

/**
 * @SWG\Swagger(
 *     schemes={"http"},
 *     host="adm.dev",
 *     consumes={"application/json"},
 *     @SWG\Info(
 *         version="1.0.0",
 *         title="API",
 *         description="Opentact API"
 *     )
 * )
 */

/**
 * @SWG\Definition(required={"grant_type", "app_uuid", "app_secret"},
 *     definition="Auth")
 */

/**
 * @SWG\Post(
 *     path="/api/token",
 *     summary="Returns access token",
 *     produces={"application/json"},
 *     tags={"auth"},
 *     @SWG\Parameter(
 *         description="Grant type",
 *         in="formData",
 *         name="grant_type",
 *         default="client_credentials",
 *         required=true,
 *         type="string",
 *     ),
 *     @SWG\Parameter(
 *         description="App UUID",
 *         in="formData",
 *         name="app_uuid",
 *         required=true,
 *         type="string",
 *     ),
 *     @SWG\Parameter(
 *         description="App Secret",
 *         in="formData",
 *         name="app_secret",
 *         required=true,
 *         type="string",
 *     ),
 *     @SWG\Parameter(
 *         description="Scope",
 *         in="formData",
 *         name="scope",
 *         type="string",
 *     ),
 *     @SWG\Response(
 *         response=200,
 *         description="Access token"
 *     ),
 *     @SWG\Response(
 *         response=500,
 *         description="Authentication failed",
 *     )
 * )
 */