package com.toedjoe.android7.data.repository

import com.toedjoe.android7.data.remote.HppPriorityData
import com.toedjoe.android7.data.remote.MobileApiService
import com.toedjoe.android7.data.remote.MonthlyTargetsData
import com.toedjoe.android7.data.remote.SaveHppBulkData
import com.toedjoe.android7.data.remote.SaveHppBulkRequest
import com.toedjoe.android7.data.remote.SaveHppProductInput
import com.toedjoe.android7.data.remote.SaveMonthlyTargetsRequest
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class PlanningRepository @Inject constructor(
    private val mobileApi: MobileApiService,
) {
    suspend fun targets(month: String): MonthlyTargetsData {
        return mobileApi.targets(month = month).data
    }

    suspend fun saveTargets(request: SaveMonthlyTargetsRequest): MonthlyTargetsData {
        return mobileApi.saveTargets(request).data
    }

    suspend fun hppPriority(search: String?, month: String? = null, limit: Int? = null): HppPriorityData {
        return mobileApi.hppPriority(
            search = search?.takeIf { it.isNotBlank() },
            limit = limit,
            month = month,
        ).data
    }

    suspend fun saveHpp(products: List<SaveHppProductInput>): SaveHppBulkData {
        return mobileApi.saveHppBulk(SaveHppBulkRequest(products)).data
    }
}
